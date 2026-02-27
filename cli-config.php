<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;

$config = new PhpFile('migrations.php'); // Or use one of the Doctrine\Migrations\Configuration\Configuration\* loaders


define('APPPATH', dirname(__FILE__) . '/application/');
define('BASEPATH', APPPATH . '/../system/');
define('ENVIRONMENT', 'development');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require_once("application/libraries/Doctrine.php");
$doctrine = new Doctrine(false);
$em = $doctrine->em;

return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($em));
