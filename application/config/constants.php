<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');


/*
 | DCL Custom types
 */

define('JOB_FAILED', 0);
define("JOB_SUCCESS", 1);
define("JOB_POSTPONE", 2);


/** AWS STUFF**/
define("AWS_REDUCED", "REDUCED_REDUNDANCY");
define("AWS_STANDARD", "STANDARD");


define("PERM_NOPERM", 0);
define("PERM_SEARCH", 10);
define("PERM_VIEWDERIVATIVES", 20);
define("PERM_DERIVATIVES_GROUP_1", 20);
define("PERM_DERIVATIVES_GROUP_2", 25);
define("PERM_ORIGINALSWITHOUTDERIVATIVES", 25);
define("PERM_CREATEDRAWERS", 30);
define("PERM_ORIGINALS", 40);
define("PERM_ADDASSETS", 50);
define("PERM_ADMIN", 60);


define("COURSE_TYPE", "Course");
define("USER_TYPE", "User");
define("JOB_TYPE", "JobCode");
define("UNIT_TYPE", "Unit");
define("REMOTE_TYPE", "Authed_remote");
define("AUTHED_TYPE", "Authed");
define("ALL_TYPE", "All");

define("DRAWER_PERMISSION", "drawer");
define("INSTANCE_PERMISSION", "instance");
define("COLLECTION_PERMISSION", "collection");

// file status
define("FILE_LOCAL", 1);
define("FILE_GLACIER_RESTORING", 2);
define("FILE_ERROR", 0);