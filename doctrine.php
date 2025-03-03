<?php


define('APPPATH', dirname(__FILE__) . '/application/');
define('BASEPATH', APPPATH . '/../system/');
define('ENVIRONMENT', 'development');

require_once("vendor/autoload.php");


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require_once("application/libraries/Doctrine.php");

use Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

foreach ($GLOBALS as $helperSetCandidate) {
    if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
        $helperSet = $helperSetCandidate;
        break;
    }
}

$doctrine = new Doctrine(false);
$em = $doctrine->em;

$commands = [];

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\Migrations\Tools\Console\ConsoleRunner as MigrationsConsoleRunner;

$cli = new \Symfony\Component\Console\Application('Doctrine Command Line Interface');

ConsoleRunner::addCommands($cli);

// Add Doctrine Migration commands
MigrationsConsoleRunner::addCommands($cli);

$cli->run();

