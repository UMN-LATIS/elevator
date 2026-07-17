<?php
defined('BASEPATH') or exit('No direct script access allowed');

class GroupTypeCatalog {
  // The built-in types every instance has. `populationWide` marks the
  // types that match a whole population rather than a chosen list, which
  // means they have no entries.
  private const BUILTIN_TYPES = [
    ALL_TYPE => [
      "name" => ALL_TYPE,
      "label" => "All",
      "helpText" => "Everyone, including signed-out visitors.",
      "populationWide" => true,
    ],
    AUTHED_TYPE => [
      "name" => AUTHED_TYPE,
      "label" => "Authenticated Users",
      "helpText" => "Anyone signed in, by any login method.",
      "populationWide" => true,
    ],
    REMOTE_TYPE => [
      "name" => REMOTE_TYPE,
      "label" => "Centrally Authenticated Users",
      "helpText" => "Users signed in through central "
        . "single sign-on (SSO).",
      "populationWide" => true,
    ],
    USER_TYPE => [
      "name" => USER_TYPE,
      "label" => "Specific People",
      "helpText" => "Specific people you choose. Add by name, email, or username.",
    ],
  ];

  private array $authHelperTypes;

  /**
   * @param array $authHelperTypes the instance auth helper's authTypes,
   *   keyed by type string (e.g. Unit, JobCode). Empty for helperless
   *   instances, which then offer only the built-in types.
   */
  public function __construct(array $authHelperTypes = []) {
    $this->authHelperTypes = $authHelperTypes;
  }

  /**
   * Every type this instance offers, keyed by type string: the built-in
   * types first, then the auth helper's own types.
   */
  public function all(): array {
    return [
      ...self::BUILTIN_TYPES,
      ...$this->authHelperTypes,
    ];
  }

  public function isValid(string $type): bool {
    return isset($this->all()[$type]);
  }

  /**
   * Whether $type comes from the auth helper rather than the built-in
   * types. Auth-helper groups match users on their value entries. The
   * built-ins never do: User matches member ids, the rest whole populations.
   */
  public function isAuthHelperType(string $type): bool {
    return !isset(self::BUILTIN_TYPES[$type]);
  }

  /**
   * Whether $type matches a whole population instead of a values list
   * (All/Authed/Authed_remote). The group holds no entries.
   */
  public function ignoresGroupValues(string $type): bool {
    return self::BUILTIN_TYPES[$type]["populationWide"] ?? false;
  }

  /**
   * Whether only an instance admin may put a group on $type. The
   * population-wide types are admin only, every other type is open to any
   * group owner.
   */
  public function isAdminOnly(string $type): bool {
    return $this->ignoresGroupValues($type);
  }

  /**
   * Suggested entry values for one group type, drawn from the signed-in
   * user's session userData.
   *
   * Auth helpers key hints by raw value with a human label, and PHP
   * coerces numeric keys to ints, so each pair is recast to a
   * {value, label} string object for the UI combobox. Local users and
   * helperless instances have no userData, so an empty list is normal,
   * not an error.
   */
  public function entryHintsFor(string $type, array $userData): array {
    // Built-in types never take entries. Guard by type category rather
    // than trusting that no auth helper ever keys its userData by a
    // built-in type name, since helpers pick their keys independently.
    if (!$this->isAuthHelperType($type)) {
      return [];
    }

    $hints = $userData[$type]["hints"] ?? [];

    $entryHints = [];
    foreach ($hints as $rawValue => $label) {
      $entryHints[] = [
        "value" => (string) $rawValue,
        "label" => (string) $label,
      ];
    }

    return $entryHints;
  }
}
