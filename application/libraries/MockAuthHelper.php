<?php

/**
 * Canned-data auth helper for local dev and CI, modeled on UMNHelper.
 *
 * Fakes the whole remote-auth surface with fixtures: remoteLogin() plants
 * the session attributes Shibboleth would have left, so visiting
 * /loginManager/remoteLogin signs you in as a canned Remote user with no
 * credentials, and populateUserData() returns fixed values and hints for
 * the UMN group types. Select it with AUTH_HELPER=MockAuthHelper.
 *
 * SECURITY: This should NEVER be run in production since it skips legit auth.
 * As a safeguard, the constructor refuses to run when ENVIRONMENT is
 * production (if somehow the ENV was set).
 */

require_once("AuthHelper.php");

class MockAuthHelper extends AuthHelper {
  public $authTypes = [
    UNIT_TYPE => [
      "name" => UNIT_TYPE,
      "label" => UNIT_TYPE
    ],
    JOB_TYPE => ["name" => JOB_TYPE, "label" => "Job Code"],
    COURSE_TYPE => ["name" => COURSE_TYPE, "label" => COURSE_TYPE],
    DEPT_COURSE_TYPE => [
      "name" => DEPT_COURSE_TYPE,
      "label" => DEPT_COURSE_TYPE,
      "helpText" => "Use % for wildcard, like DEPT.NUMBER% to include all sections."
    ],
    STATUS_TYPE => ["name" => STATUS_TYPE, "label" => "Student Status"],
    EMPLOYEE_TYPE => ["name" => EMPLOYEE_TYPE, "label" => "Employee Type"]
  ];

  // The one identity remoteLogin() signs in.
  const MOCK_REMOTE_USER = [
    "username" => "mockinstructor",
    "displayName" => "Mock Instructor",
    "email" => "mockinstructor@example.edu",
  ];

  // Stand-in for the external people directory (bandaid at UMN). These
  // back findById/autocomplete, so member-add by remote id works.
  const MOCK_DIRECTORY = [
    [
      "username" => "mockstudent",
      "displayName" => "Mock Student",
      "email" => "mockstudent@example.edu",
    ],
    [
      "username" => "mockstaff",
      "displayName" => "Mock Staff",
      "email" => "mockstaff@example.edu",
    ],
    [
      "username" => "mockprofessor",
      "displayName" => "Mock Professor",
      "email" => "mockprofessor@example.edu",
    ],
  ];

  // The guard is an allowlist so an unexpected or missing CI_ENV fails
  // closed rather than open (index.php defaults ENVIRONMENT to
  // development when CI_ENV is unset).
  const ALLOWED_ENVIRONMENTS = ['development', 'local', 'testing'];

  public function __construct() {
    if (!in_array(ENVIRONMENT, self::ALLOWED_ENVIRONMENTS, true)) {
      show_error('MockAuthHelper grants credential-less login and only runs in development, local, or testing environments. Set AUTH_HELPER to your real auth helper.', 500);
    }
    parent::__construct();
  }

  /**
   * Fake the Shibboleth handshake: plant the session attributes the rest
   * of LoginManager::remoteLogin reads, then return false so it continues
   * into user lookup and provisioning instead of redirecting.
   */
  public function remoteLogin($redirectURL, $noForcedAuth = false): bool {
    $this->CI->session->set_userdata([
      'userAuthField' => self::MOCK_REMOTE_USER['username'],
      'userAttributesCache' => [
        'uniqueIdentifier' => self::MOCK_REMOTE_USER['username'],
        'isGuest' => 'N',
      ],
    ]);
    return false;
  }

  public function getUserIdFromRemote($map = null): ?string {
    if ($map) {
      return $map["uniqueIdentifier"];
    }
    return $this->CI->session->userdata('userAuthField');
  }

  /**
   * Provision the canned Remote user on first mock login.
   *
   * Superadmin so the mock user can exercise admin screens without
   * seeded permission rows. Safe only because the production guard
   * keeps this class out of real deployments.
   */
  public function createUserFromRemote($userOverride = null, $map = null) {
    $user = new Entity\User;
    $user->setUsername(self::MOCK_REMOTE_USER['username']);
    $user->setDisplayName(self::MOCK_REMOTE_USER['displayName']);
    $user->setEmail(self::MOCK_REMOTE_USER['email']);
    $user->setUserType("Remote");
    $user->setHasExpiry(false);
    $user->setCreatedAt(new \DateTime("now"));
    $user->setInstance($this->CI->instance);
    $user->setIsSuperAdmin(true);
    $user->setFastUpload(false);
    $this->CI->doctrine->em->persist($user);
    $this->CI->doctrine->em->flush();
    return $user;
  }

  /**
   * Canned values and hints for every type, in UMNHelper's exact shape.
   */
  public function populateUserData($user): array {
    return [
      COURSE_TYPE => [
        "values" => ["12345", "23456"],
        "hints" => [
          12345 => "ART.1234.001",
          23456 => "CSCI.2021.010",
        ],
      ],
      DEPT_COURSE_TYPE => [
        "values" => ["ART.1234.001", "CSCI.2021.010"],
        "hints" => [
          "ART.1234.001" => "Drawing I",
          "CSCI.2021.010" => "Machine Architecture",
        ],
      ],
      JOB_TYPE => ["values" => ["9403"], "hints" => [
        '9403' => "Instructor",
        '9404' => "TA",
        '9405' => "Grader",
      ]],
      UNIT_TYPE => [
        "values" => ["CLA-ART", "CSE-CSCI"],
        "hints" => ["CLA-ART" => "CLA-ART", "CSE-CSCI" => "CSE-CSCI"],
      ],
      STATUS_TYPE => [
        "values" => ["GRAD"],
        "hints" => ["UGRD" => "Undergraduate", "GRAD" => "Graduate"],
      ],
      EMPLOYEE_TYPE => [
        "values" => ["Faculty"],
        "hints" => [
          "Faculty" => "Faculty",
          "Student" => "Student",
          "Staff" => "Staff",
        ],
      ],
    ];
  }

  public function getGroupMapping($userData): array {
    $outputArray = [];
    if (!$userData || !is_array($userData)) {
      return $outputArray;
    }
    foreach ($userData as $key => $value) {
      $outputArray[$key] = $value["values"];
    }
    return $outputArray;
  }

  public function findById($key, $createMissing = false): array {
    return $this->findInMockDirectory($key);
  }

  public function findUserByUsername($key, $createMissing = false): array {
    return $this->findInMockDirectory($key);
  }

  public function findUserByName($key, $createMissing = false): array {
    return $this->findInMockDirectory($key);
  }

  public function autocompleteUsername($partialUsername): array {
    $outputArray = parent::autocompleteUsername($partialUsername);

    foreach ($this->findInMockDirectory($partialUsername) as $user) {
      $isDuplicate = false;
      foreach ($outputArray as $entry) {
        if ($entry["username"] == $user->getUsername()) {
          $isDuplicate = true;
        }
      }
      if (!$isDuplicate) {
        $outputArray[] = [
          "name" => $user->getDisplayName(),
          "email" => $user->getEmail(),
          "completionId" => $user->getId(),
          "username" => $user->getUsername(),
        ];
      }
    }

    return $outputArray;
  }

  /**
   * Case-insensitive substring match on username or display name.
   *
   * @return Entity\User[] unsaved records, the same convention as
   * UMNHelper::findUser, callers persist them on first use
   */
  private function findInMockDirectory(string $key): array {
    $matches = [];
    foreach (self::MOCK_DIRECTORY as $person) {
      $haystack = strtolower($person["username"] . " " . $person["displayName"]);
      if (str_contains($haystack, strtolower($key))) {
        $user = new Entity\User;
        $user->setUsername($person["username"]);
        $user->setDisplayName($person["displayName"]);
        $user->setEmail($person["email"]);
        $matches[] = $user;
      }
    }
    return $matches;
  }
}
