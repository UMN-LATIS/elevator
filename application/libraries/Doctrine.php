<?php

use Doctrine\Common\ClassLoader,
    Doctrine\ORM\Tools\Setup,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\EventManager,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\DBAL\Logging\Profiler;


class Doctrine
{

    public $em;
    public $redisHost;

    public function __construct()
    {

//        Setup::registerAutoloadDirectory(__DIR__);

        // Load the database configuration from CodeIgniter

        $this->connect();

    }

    public function connect() {
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

        // With this configuration, your model files need to be in application/models/Entity
        // e.g. Creating a new Entity\User loads the class from application/models/Entity/User.php
        $models_namespace = 'Entity';
        $models_path = APPPATH . 'models';
        $proxies_dir = APPPATH . 'models/Proxies';
        $metadata_paths = array(APPPATH . 'doctrine');

        // Set $dev_mode to TRUE to disable caching while you develop
        //$config = Setup::createAnnotationMetadataConfiguration($metadata_paths, $dev_mode = true, $proxies_dir);
        //TODO: enable caching

        // TODO TODO TODO

        $doctrineConfig = Setup::createXMLMetadataConfiguration($metadata_paths, $dev_mode = true, $proxies_dir);
        // $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

      // $cache = new ApcCache;
        $redis = new Redis();
        require(APPPATH . 'config/config.php');
        $redis->connect($config["redis"], $config["redisPort"]);
        $this->redisHost = $redis;
        $redisCache = new \Doctrine\Common\Cache\RedisCache();
        $redisCache->setRedis($redis);

        $doctrineConfig->setMetadataCacheImpl($redisCache);
        $doctrineConfig->setQueryCacheImpl($redisCache);
        $doctrineConfig->setResultCacheImpl($redisCache);
        // $config->setQueryCacheImpl($cache);

        //$logger = new \Doctrine\DBAL\Logging\Profiler;
        //$config->setSQLLogger($logger);
        $this->em = EntityManager::create($connection_options, $doctrineConfig);
        $loader = new ClassLoader($models_namespace, $models_path);
        $loader->register();
    }

    public function reset() {
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

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

}
