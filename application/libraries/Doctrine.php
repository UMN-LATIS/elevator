<?php

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\Middleware;
use    Doctrine\ORM\Tools\Setup,
    Doctrine\ORM\EntityManager;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Psr16Cache;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class Doctrine
{

    public EntityManager $em;
    public $redisHost;

    public function __construct($useCache = null)
    {

//        Setup::registerAutoloadDirectory(__DIR__);

        // Load the database configuration from CodeIgniter
        $this->connect($useCache);

    }

    public function connect($useCache = null) {
        require(APPPATH . 'config/database.php');
        $connection_options = array(
            'driver'        => $db['default']['doctrineDriver'],
            'user'          => $db['default']['username'],
            'password'      => $db['default']['password'],
            'host'          => $db['default']['hostname'],
            'port'          => $db['default']['port'],
            'dbname'        => $db['default']['database'],
            'charset'       => $db['default']['char_set'],
            'driverOptions' => array(
                'charset'   => $db['default']['char_set'],
            ),
        );



		// $logger = new Logger('sql_logger');
		// $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        // With this configuration, your model files need to be in application/models/Entity
        // e.g. Creating a new Entity\User loads the class from application/models/Entity/User.php
        $models_namespace = 'Entity';
        $models_path = APPPATH . 'models';
        $proxies_dir = APPPATH . 'models/Proxies';
        $metadata_paths = array(APPPATH . 'models/Entity');

        // Set $dev_mode to TRUE to disable caching while you develop
        

        // $doctrineConfig = Setup::createXMLMetadataConfiguration($metadata_paths, $dev_mode = true, $proxies_dir);
        // $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        require(APPPATH . 'config/config.php');
    
        if($useCache === null) {
            $useCache = $config["enableCaching"];
        }
        // $middleware = new Middleware($logger);
        $cache = null;
        $doctrineCache = null;

        if($useCache && $config["redis"]) {
            $redis = new Redis();
            
            $redis->connect($config["redis"], $config["redisPort"]);
            $this->redisHost = $redis;

            $cache = new RedisAdapter($redis, namespace:'doctrine');
        }

        $doctrineConfig = ORMSetup::createAttributeMetadataConfiguration(paths:$metadata_paths,isDevMode: !($useCache),proxyDir: $proxies_dir,cache: $cache);
        $doctrineConfig->setProxyDir($proxies_dir);
        $doctrineConfig->setAutoGenerateProxyClasses(true);
        $config = new Configuration();
        // $config->setMiddlewares([$middleware]);

        if($cache) {
            $doctrineConfig->setMetadataCache($cache);
            $doctrineConfig->setQueryCache($cache);
            
            $doctrineConfig->setResultCache($cache);
        }
        


        //$logger = new \Doctrine\DBAL\Logging\Profiler;
        //$config->setSQLLogger($logger);
        $connection = \Doctrine\DBAL\DriverManager::getConnection($connection_options, $config);
        if(!Type::hasType('uuid')) {
            Type::addType('uuid', 'Ramsey\Uuid\Doctrine\UuidType');
        }
        

        $this->em = new EntityManager($connection, $doctrineConfig);
    }

    public function reset() {
        $this->em->getConnection()->close();
        $this->em->getConnection()->getServerVersion(); // forces a reconnect

    }

    public function extendTimeout() {

        ini_set('default_socket_timeout', 28800);
        require(APPPATH . 'config/database.php');
    	if($db['default']['doctrineDriver'] == "pdo_mysql") {
    	        ini_set('mysql.connect_timeout', 28800);
    	        $statement = $this->em->getConnection()->prepare("set SESSION wait_timeout = 28800");
    	        $statement->execute();
    	        $statement = $this->em->getConnection()->prepare("set SESSION interactive_timeout = 28800");
    	        $statement->execute();
    	        $statement = $this->em->getConnection()->prepare("SHOW SESSION VARIABLES");
            	$statement->execute();
    	}

    }

    public function getCache($cacheNamespace) {
		$redis = new Redis();
        $CI = &get_instance();
        $redis->connect($CI->config->item("redis"),$CI->config->item("redisPort"));
		$cache = new RedisAdapter($redis, $cacheNamespace);
		return new Psr16Cache($cache);
	}

}
