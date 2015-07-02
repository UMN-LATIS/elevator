<?php

namespace ShibPoseur;

/**
 * ShibPoseur 
 * 
 * @package ShibPoseur
 * @copyright [Copyright]
 * @author Michael Berkowski <mjb@umn.edu> 
 */
class ShibPoseur
{
  /**
   * The dynamically constructed cookie name into which a fake session gets serialized
   * 
   * @var string
   * @access protected
   */
  protected $poseur_cookie;
  /**
   * The name of the Shibboleth SP session cookie
   * 
   * @var string
   * @access protected
   */
  protected $shibsession_cookie;
  /**
   * Shibboleth SP session id
   * 
   * @var string
   * @access protected
   */
  protected $shibsession_id;
  /**
   * @var string
   * @access protected
   */
  protected $shibsession_index;
  /**
   * Number of seconds the session is "valid" for before being repopulated
   * 
   * @var string
   * @access protected
   */
  protected $shibsession_seconds = 7200;
  /**
   * Shibboleth attributes
   * 
   * @var array
   * @access protected
   */
  protected $attributes = array();
  /**
   * Attributes which may be set in the server environment
   * These are interpolated from the config file and used when parsing JSON
   * 
   * @var array
   * @access protected
   */
  protected $valid_attributes = array(
    'Shib-Session-Index',
    'Shib-Session-ID',
    'Shib-Authentication-Instant'
  );
  /**
   * Configuration
   * 
   * @var array
   * @access protected
   */
  protected $config = array(
    // Use HTTP_ headers to simulate ShibUseHeaders
    'ShibUseHeaders' => false,
    'lifetime' => 28800,
  );
  /**
   * Array of fixture users as defined in the config file
   * 
   * @var array
   * @access protected
   */
  protected $users = array();
  /**
   * Array of method names available to the Shibboleth.sso handler
   * 
   * @static
   * @var string
   * @access protected
   */
  protected static $public_actions = array(
    'Login',
    'Logout',
    'Session'
  );

  /**
   * Constructor
   * 
   * @param string $configfile Path to the configuration file. If null, uses the default ./shibconfig.php
   * @access public
   * @return bool
   */
  public function __construct($configfile = null)
  {
    // Cookie which holds shib session values across requests (also allows non-PHP things to access it)
    $this->poseur_cookie = '_shibposeur_' . str_replace('.', '_', $_SERVER['SERVER_NAME']);
    // Big long fake _shibsession_ cookie name
    $this->shibsession_cookie = '_shibsession_' . str_repeat('123456abcd', 11);
    // Just a string that looks something like a session cookie value
    $this->shibsession_id = '_' . md5(microtime() . rand());
    // Fake value that looks like the session index (64char)
    $this->shibsession_index = rtrim($this->shibsession_id . strrev($this->shibsession_id), '_');

    $shibposeur_config = $this->loadConfigFile($configfile);
    $this->users = $shibposeur_config['users'];

    $user_attrs = array();
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['shibposeur-login'])) {
      $this->loadAttributes($shibposeur_config);
      $this->loadUser($_POST, $shibposeur_config);
    }

    $this->loadValidAttributes($shibposeur_config);
    $this->populateShibParams($this->attributes, array_keys($this->valid_attributes));

    // Load additional configurations
    if (isset($shibposeur_config['config']) && is_array($shibposeur_config['config'])) {
      $this->config = array_merge($this->config, $shibposeur_config['config']);
    }
  }
  /**
   * Shibboleth Login action
   * 
   * @param string $target Destination URL ?target=http://...
   * @access public
   * @return void
   */
  public function Login($target = '/')
  {
    // On the first pass, if no cookie is yet set, show the fake login screen
    // It will post back to this script, set & remove a temporary cookie
    if (!isset($_COOKIE[$this->poseur_cookie]) && !isset($_COOKIE[$this->poseur_cookie . "_loginscreen"])) {
      setcookie($this->poseur_cookie . "_loginscreen", 'true', time() + 60, '/');
      $this->showView(__FUNCTION__, array('target' => $target));
    }
    // Load the cookie if present, and we're done until redirection
    if ($this->hasSessionCookie()) {
      $this->loadCookie();
    }
    // No cookie, so act like a new login
    else {
      // Set the runtime-configured attribute set
      $runtime_attrs = array(
        'Shib-Session-ID' => $this->shibsession_id,
        'Shib-Session-Index' => $this->shibsession_index,
        'Shib-Authentication-Instant' => date('c')
      );
      $this->attributes = array_merge($this->attributes, $runtime_attrs);

      $this->populateShibParams($this->attributes, array_keys($this->valid_attributes));
      // Serialize the whole thing into a cookie as JSON
      // so that it can be decoded without redirection or stomping on $_SESSION
      $cookie = base64_encode(json_encode($this->attributes));
      setcookie($this->poseur_cookie, $cookie, time() + $this->config['lifetime'], '/');

      // And unset the login screen cookie so it doesn't appear again
      setcookie($this->poseur_cookie . "_loginscreen", "", time() - 3600, '/');

      // And set a fake session cookie for the Shibboleth SP
      setcookie($this->shibsession_cookie, $this->shibsession_id, time() + $this->shibsession_seconds, '/');
    }
    $this->redirect($target);
  }
  /**
   * Shibboleth Logout action
   * 
   * @param string $return Destination URL ?return=http://...
   * @param boolean $show_view Should the logout screen be shown?
   * @access public
   * @return void
   */
  public function Logout($return = '/', $show_view = true)
  {
    $this->clearShibParams(array_keys($this->attributes));
    $this->attributes = array();
    // Get rid of the fake session cookie and the data cookie
    setcookie($this->poseur_cookie, '', time() - 3600, '/');
    setcookie($this->poseur_cookie . '_loginscreen', '', time() - 3600, '/');
    setcookie($this->shibsession_cookie, '', time() - 3600, '/');
    // And kill the $_COOKIE superglobal for this execution
    unset($_COOKIE[$this->poseur_cookie]);
    unset($_COOKIE[$this->poseur_cookie . '_loginscreen']);
    unset($_COOKIE[$this->shibsession_cookie]);

    if ($show_view) {
      $this->showView(__FUNCTION__, array('return' => $return));
    }
  }
  /**
   * Print info about the Shibboleth session
   * 
   * @access public
   * @return void
   */
  public function Session()
  {
    // Load the cookie if present, and we're done until redirection
    if ($this->hasSessionCookie()) {
      $this->loadCookie();
    }
    $this->showView(__FUNCTION__);
  }
  /**
   * Return the name of the session state cookie set by ShibPoseur
   * 
   * @access public
   * @return string
   */
  public function hasSessionCookie()
  {
    return !empty($_COOKIE[$this->poseur_cookie]);
  }
  /**
   * Load the Shibboleth properties from the session state cookie set by ShibPoseur
   * 
   * @access public
   * @return void
   */
  public function loadCookie()
  {
    $cookie_params = json_decode(base64_decode($_COOKIE[$this->poseur_cookie]), true);
    $this->populateShibParams($cookie_params, array_keys($this->valid_attributes));
  }
  public function hasLoginScreenCookie() {
    return !empty($_COOKIE[$this->poseur_cookie . '_loginscreen']);
  }
  /**
   * Process location redirection
   * 
   * @param string $location 
   * @access public
   * @return void
   */
  public function redirect($location)
  {
    header("Location: $location");
    exit();
  }
  /**
   * Load the system environment with Shibboleth attributes 
   * 
   * @param array $server_params
   * @param array $reference_params Parameters allowed to be set
   * @access protected
   * @return void
   */
  protected function populateShibParams($server_params, $reference_params = null)
  {
    ksort($server_params);
    foreach ($server_params as $param => $value) {
      // Only allow parameters to be set which exist as keys in $reference_params
      // This is mainly useful for setting params from a json cookie
      if (is_array($reference_params) && in_array($param, $reference_params)) {
        // Set the HTTP header if needed
        if ($this->config['ShibUseHeaders']) {
          $param = self::attributeHTTPHeaderName($param);
        }
        $_SERVER[$param] = $value;
        $this->attributes[$param] = $value;
      }
    }
  }
  /**
   * Clear the environment of Shibboleth attributes
   * 
   * @param mixed $server_params Parameters to unset
   * @access protected
   * @return bool
   */
  protected function clearShibParams($server_params)
  {
    foreach ($server_params as $param) {
      if (isset($_SERVER[$param])) unset($_SERVER[$param]);
    }
  }
  /**
   * Load the config file
   * 
   * @param string $configfile File path. If omitted, use ./shibconfig.php
   * @access protected
   * @return void
   */
  protected function loadConfigFile($configfile = null)
  {
    // Load the Shibboleth session parameter configuration file
    if (empty($configfile)) {
      $configfile = __DIR__ . '/shibconfig.php';
    }
    if (file_exists($configfile)) {
      // Include file, which expects to bring $shibposeur_config into the current scope
      include $configfile;
    }
    else throw new \Exception("Shibboleth configuration file $configfile could not be found");

    // Make sure the conf array was actually defined
    if (!isset($shibposeur_config) || !is_array($shibposeur_config)) {
      throw new \Exception("Shibboleth configuration file $configfile did not define an array \$shibposeur_config");
    }
    // And make sure it has mock users and common attributes
    foreach (array('users', 'common_attributes') as $key) {
      if (!isset($shibposeur_config[$key]) || !is_array($shibposeur_config[$key])) {
        throw new \Exception("Shibboleth configuration file $configfile did not define required array \$key");
      }
    }
    return $shibposeur_config;
  }
  /**
   * Load up all the attribute-related object properties
   * 
   * @param array $shibposeur_config 
   * @access protected
   * @return void
   */
  protected function loadValidAttributes(array $shibposeur_config)
  {
    // Collect permissible attributes from the user fixtures
    $attr = &$this->valid_attributes;
    array_walk_recursive($shibposeur_config['users'], function ($item, $key) use ($attr) {
      $this->valid_attributes[] = $key;
    });

    // And merge them with the runtime attributes and common attributes
    $this->valid_attributes = array_unique(array_merge(
      array_keys($shibposeur_config['common_attributes']),
      $this->valid_attributes
    ));

    return;
  }
  /**
   * Merge common attributes with attributes already set
   * 
   * @param array $shibposeur_config Configuration array 
   * @access protected
   * @return void
   */
  protected function loadAttributes(array $shibposeur_config)
  {
    // And merge the base attributes as full key/value
    $this->attributes = array_merge(
      $shibposeur_config['common_attributes'],
      $this->attributes
    );
  }
  /**
   * Load the requested user fixture
   * 
   * @param array $post Input user (from login.php view POST)
   * @param array $shibposeur_config Configuration array
   * @access protected
   * @return void
   */
  protected function loadUser(array $post, array $shibposeur_config)
  {
    $user = array();
    if (array_key_exists($post['shibposeur-uid'], $this->users)) {
      $user = $this->users[$post['shibposeur-uid']];
    }
    // uid passed in freetext otherwise
    else if (!empty($post['shibposeur-uid-other'])) {
      $user['uid'] = $post['shibposeur-uid-other'];
      $user['eppn'] = "{$post['shibposeur-uid-other']}@example.com";
    }
    else {
      // Or a default
      $user['uid'] = 'shibposeur';
      $user['eppn'] = 'shibposeur@example.com';
    }
    // Set REMOTE_USER if it wasn't forced in the loaded user
    if (empty($user['REMOTE_USER'])) {
      if (!empty($shibposeur_config['REMOTE_USER_attribute']) && !empty($user[$shibposeur_config['REMOTE_USER_attribute']])) {
        $user['REMOTE_USER'] = $user[$shibposeur_config['REMOTE_USER_attribute']];
      }
      // Default to eppn
      else {
        $user['REMOTE_USER'] = $user['eppn'];
      }
    }
    // Write the user attrs onto the object attrs
    $this->attributes = array_merge($this->attributes, $user);
    return;
  }
  /**
   * Display a view for $view_name.  View files are expected to be all lower-case
   * and $view_name will be downcased.  If $exit is true, the script will be terminated
   * with exit();
   * 
   * @param string $view_name Will be downcased
   * @param array $vars Associative array of variables to pass to the view, will be extracted into scope
   * @param bool $exit Terminate execution after displaying the view
   * @access protected
   * @return bool
   */
  protected function showView($view_name, $vars = array(), $exit = true)
  {
    // Pull variables into scope to use in the view
    if (!empty($vars)) {
      extract($vars);
    }
    $view = __DIR__ . '/view/' . strtolower($view_name) . '.php';

    if (file_exists($view)) {
      include $view;
      if ($exit) {
        exit();
      }
    }
    else {
      throw new Exception\ViewNotFoundException("View $view was not found");
    }
  }
  /**
   * Return the HTTP_ header version of the supplied $attribute name
   * which replaces hyphens by underscores and prepends HTTP_
   * like: Shib-Identity-Provider -> HTTP_SHIB_IDENTITY_PROVIDER
   * 
   * @param string $attribute 
   * @access protected
   * @return string
   */
  protected static function attributeHTTPHeaderName($attribute)
  {
    return 'HTTP_' . strtoupper(str_replace('-', '_', $attribute));
  }
  /**
   * Return an array of allowed actions
   * 
   * @static
   * @access public
   * @return array
   */
  public static function getPublicActions()
  {
    return self::$public_actions;
  }
}
?>
