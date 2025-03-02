<?php

define('APPPATH', dirname(__FILE__) . '/application/');
define('BASEPATH', APPPATH . '/../system/');
define('ENVIRONMENT', 'development');

require_once("vendor/autoload.php");


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require_once("application/libraries/Doctrine.php");
$doctrine = new Doctrine(false);
$em = $doctrine->em;

return $em->getConnection();
