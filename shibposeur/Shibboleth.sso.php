<?php
namespace ShibPoseur;

/**
 * This file serves as the primary controller to which all Shibboleth actions are routed
 * 
 * For PHP applications running on Apache, it is recommended to use the auto_prepend_file directive
 * in .htaccess or httpd.conf to force the file to be included
 * 
 * File inclusion is necessary for the cookie parameters to be read and set 
 * on occasions when a Shibboleth.sso session action is not performed. This mimics
 * the behavior of the Shibboleth Native SP, automatically populating the $_SERVER
 * environment.
 *
 * To route Shibboleth action requests to this script, configure Apache mod_rewrite accordingly:
 *
 * # If used in .htaccess at the site document root:
 * RewriteEngine On
 * RewriteRule ^Shibboleth\.sso(?:/(.*)) path/to/shibposeur/Shibboleth.sso.php?shibaction=$1 [L,QSA]
 *
 * # If used in VirtualHost or Server context:
 * RewriteEngine On
 * RewriteRule ^/Shibboleth.sso(?:(.*)) /path/to/shibposeur/Shibboleth.sso.php?shibaction=$1 [L,QSA]
 */

require_once __DIR__ . '/ShibPoseur.php';
require_once __DIR__ . '/Exception/ConfigurationException.php';
require_once __DIR__ . '/Exception/ViewNotFoundException.php';

$poseur = new ShibPoseur();

// Empty shibaction parameter indicates a basic file include
// which populates the existing faux-shib session
if (empty($_GET['shibaction'])) {
  // If this script was not requested via http and is a file include
  // then just load up the existing session
  if (realpath($_SERVER['SCRIPT_FILENAME']) !== realpath(__FILE__)) {
    // Load the session from the cookie
    if ($poseur->hasSessionCookie()) {
      $poseur->loadCookie();
    }
  }
  // This was an HTTP request but with no requested action, which would be an 
  // error state in a real shib SP:
  else {
    throw new Exception\ConfigurationException();
  }
  return;
}
else {
  // If forcing auth, do a silent logout first
  if (!empty($_GET['forceAuthn']) && !$poseur->hasLoginScreenCookie()) {
    $poseur->Logout('', false);
  }
  $url = !empty($_GET['target']) ? trim($_GET['target']) : (!empty($_GET['return']) ? trim($_GET['return']) : '/');
  if (in_array($_GET['shibaction'], ShibPoseur::getPublicActions())) {
    $poseur->{$_GET['shibaction']}($url);
  }
  else {
    throw new Exception\ConfigurationException();
  }
}
