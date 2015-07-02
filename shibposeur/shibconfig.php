<?php
/*
 * Configuration Defaults:
 * ShibUseHeaders => false
 *
 * @optional
 * @var array
 */
$shibposeur_config['config'] = array(
  'ShibUseHeaders' => false,
  // Which attribute should populate REMOTE_USER?
  // Defaults to eppn
  'REMOTE_USER_attribute' => 'eppn'
);

/**
 * Array of params which will get stuffed into $_SERVER to pose as shibboleth attributes
 *  The JSON-encoded cookie may only set these parameters.
 *
 * @required
 * @var array
 */
$shibposeur_config['common_attributes'] = array(
  'Shib-Application-ID' => 'default',
  'Shib-Identity-Provider' => 'https://idp2.shib.umn.edu/idp/shibboleth',
  'Shib-Authentication-Method' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:unspecified',
  'Shib-AuthnContext-Class' => 'urn:oasis:names:tc:SAML:2.0:ac:classes:unspecified',
);

/**
 * Mock users, indexed by username, with attributes as key => value pairs
 *
 * @required
 * @var array
 */
$shibposeur_config['users'] = array(
  'andersen' => array(
    'employeeNumber' => '4444444',
    'umndid' => '5scyi59j8',
    'givenName' => 'Ryan',
    'surname' => 'Andersen',
    'uid' => 'andersen',
    'eppn' => 'andersen@umn.edu',
    'umnAffinity' => 'tc.staff.lib.372A.9775',
    'umnLibAccess' => '2;1;9',
    'umnLibUserType' => 'IMNU:49',
    'umnPatronID' => '2222222222',
    'umnPersonType' => 'Staff;Degree',
    'umnRole' => 'tc.dgre.gs.music.ma;tc.staff.lib.372A.9775',
    'umnUCard' => '1234567890',
    'umnCourse' => '123;456',
    'umnJobCode' => '9513;5964'
  ),
  'mcfa0086' => array(
    'employeeNumber' => '2328381',
    'umndid' => '3dci3yxub',
    'givenName' => 'Colin',
    'surname' => 'McFadden',
    'uid' => 'mcfa0086',
    'eppn' => 'mcfa0086@umn.edu',
    'umnAffinity' => 'tc.staff.lib.372A.9775',
    'umnLibAccess' => '2;1;9',
    'umnLibUserType' => 'IMNU:49',
    'umnPatronID' => '2222222222',
    'umnPersonType' => 'Staff;Degree',
    'umnRole' => 'tc.dgre.gs.music.ma;tc.staff.lib.372A.9775',
    'umnUCard' => '1234567890',
    'umnCourse' => '123;456',
    'umnJobCode' => '9513;5964'
  ),
  'mjb' => array(
    'employeeNumber' => '1234567',
    'umndid' => 'y2hh94upx',
    'givenName' => 'Fake',
    'surname' => 'Spoof',
    'uid' => 'mjb',
    'eppn' => 'mjb@example.com',
    'umnAffinity' => 'tc.staff.lib.372A.9775',
    'umnLibAccess' => '2;1;9',
    'umnLibUserType' => 'IMNU:49',
    'umnPatronID' => '2222222222',
    'umnPersonType' => 'Staff;Degree',
    'umnRole' => 'tc.dgre.gs.music.ma;tc.staff.lib.372A.9775',
    'umnUCard' => '1234567890'
  ),
  'smith' => array(
    'employeeNumber' => '9876543',
    'umndid' => '26235632',
    'givenName' => 'Not',
    'surname' => 'Real',
    'uid' => 'smith',
    'eppn' => 'smith@example.com',
    'umnAffinity' => 'tc.grad.cla.abc.def',
    'umnLibAccess' => '2;1;9',
    'umnLibUserType' => 'IMNU:49',
    'umnPatronID' => '2222222222',
    'umnPersonType' => 'Staff;Degree',
    'umnRole' => 'tc.grad.cla.abc.def',
    'umnUCard' => '2ucsa9999999xxx'
  )
);
?>
