<?php
/**
 * ConfiguradorBase.php
 * Main configurator base class that configures all application resources as DB Connections, Caches, Loggers, ...
 */

namespace gcf;

use connectionPool;
use gcf\cache\cachePlugin;
use gcf\database\errorDriverDB;
use Laminas;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

require_once "Zend/Log.php";

abstract class ConfiguratorBase
{
    public $dirs;

    /**
     * @var web\templates\templateEngine
     */
    public $tmpl;

    /**
     * @var array
     */
    private $tmplClasses;

    /**
     * @var \base_dades
     */
    public $db;

    /**
     * @var Laminas\Config\Config
     */
    protected $config;

    /**
     * @var cache\cachePlugin
     */
    protected $cache;

    /**
     * @var \gcf\tasks\taskPlugin
     */
    protected $jobExecutor;

    /**
     * @var string action or module name
     */
    protected $accio;

    /**
     * @var array
     */
    private static $_instances = [];

    /**
     * @var ServiceManager
     */
    private $serviceManager;

    /**
     * ConfiguratorBase constructor.
     *
     */
    protected function __construct()
    {
        $this->dirs = [];
        $this->tmplClasses = [];
        $this->serviceManager = new ServiceManager();
    }

    /**
     * Get configuration instance
     * @return ConfiguratorBase|null
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class();
        }
        return self::$_instances[$class];
    }


    /**
     * Get main database application connection
     * @throws \Exception
     */
    private function initDBMain()
    {
        try {
            $dbMain = $this->config->general->maindb;
            $dbPool = \connectionPool::getInstance();
            if (!$dbPool)
                throw new \Exception("ERROR Configurador: El pool de connexions de bases de dades no s'ha inicialitzat correctament!");

            $this->db = $dbPool->$dbMain->getConnection();
        } catch (errorDriverDB $e) {
            error_log($e->getMessage());
            throw new \Exception("DB Error: " . $e->getMessage());
        }
    }

    /**
     * Initialize all loggers configured into configuration file
     */
    private function initLoggers()
    {
        try {
            $loggers = explode(",", $this->config->logging->loggers);
            foreach ($loggers as $loggerName) {
                $logOptions = $this->config->logging->$loggerName;
                $this->serviceManager->setService("logger." . $loggerName, \Zend_Log::factory($logOptions->toArray()));
            }
        } catch (\Zend_Log_Exception $e) {
            error_log("Logger init error: " . $e->getMessage());
        }
    }

    /**
     * Set application configuration from .ini file
     * @param Laminas\Config\Config $config
     * @throws \Exception
     */
    public function setConfig(Laminas\Config\Config $config)
    {
        $this->config = $config;

        $this->initLoggers();
        $this->initDBMain();

        $paths = $config->paths;
        $this->dirs = ["root" => $paths->path->root,
            "app" => $paths->path->app,
            "appbase" => $paths->path->appbase,
            "imatges" => $paths->path->imatges,
            "include" => $paths->path->include,
            "templates" => $paths->path->templates];

        if (!$config->general->template_engines)
            new \Exception("There not defined template_engines into configuration file!");

        foreach (explode(",", $config->general->template_engines) as $engineName) {
            $engineName = trim($engineName);
            $engineClassName = "gcf\\web\\templates\\" . $engineName;

            /** @var \gcf\web\templates\templateEngine $tmplObj */
            $tmplObj = new $engineClassName();
            $tmplObj->setBasedir($this->dirs["templates"]);
            $tmplObj->setAssetsdir($paths->path->imatges);
            $this->tmplClasses[$engineName] = $tmplObj;
        }

        $this->tmpl = $this->tmplClasses["patEngine"];
    }

    /**
     * Get template engine object
     * @param $engineName
     * @return \gcf\web\templates\templateEngine
     */
    public function getTmplEngine($engineName)
    {
        return $this->tmplClasses[$engineName];
    }

    /**
     * Get logger instance
     * @param string $loggerName
     * @return \Zend_Log
     */
    public function getLoggerObject($loggerName = null)
    {
            if ($loggerName === null) {
                $loggerName = "logger." . $this->accio;
            } else $loggerName = "logger.$loggerName";

            if (!$this->serviceManager->has($loggerName))
                $loggerName = "logger.general";

            if (!$this->serviceManager->has($loggerName))
                return null;

            return $this->serviceManager->get($loggerName);
    }

    /**
     * Get application configuration object
     * @return \Laminas\Config\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get cache connection object
     * @return cachePlugin
     */
    public function getCache()
    {
        if ($this->cache instanceof cachePlugin)
            return $this->cache;

        $dbindex = null;
        $type = $this->config->cache->type;
        $host = $this->config->cache->host;
        $port = $this->config->cache->port;
        if ($this->config->cache->dbindex)
            $dbindex = $this->config->cache->dbindex;

        $classPlugin = "\\gcf\\cache\\$type" . "Plugin";
        if (!class_exists($classPlugin))
            return new cache\dummyPlugin(null);

        $cnx = new \stdClass();
        $cnx->host = $host;
        $cnx->port = $port;
        try {
            /** @var cachePlugin $classPlugin */
            $this->cache = new $classPlugin([$cnx], true, $dbindex);
        } catch (\gcf\cache\cacheDriverError $e) {
            $this->getLoggerObject()->err(__CLASS__ . " Cache driver error: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->getLoggerObject()->err(__CLASS__ . " Cache general error: " . $e->getMessage());
            return new \gcf\cache\dummyPlugin(null);
        }

        return $this->cache;
    }

    /**
     * Get job server connection object
     * @return \gcf\tasks\taskPlugin
     */
    public function getJobExecutor()
    {
        if ($this->jobExecutor instanceof \gcf\tasks\taskPlugin)
            return $this->jobExecutor;

        $type = $this->config->jobserver->type;
        $host = $this->config->jobserver->host;
        $port = $this->config->jobserver->port;

        $classPlugin = "\\gcf\\tasks\\$type" . "Plugin";
        if (!class_exists($classPlugin)) {
            $this->getLogger()->err("El driver $classPlugin no existeix!");
            return null;
        }
        try {
            /** @var \gcf\tasks\taskPlugin $classPlugin */
            $this->jobExecutor = new $classPlugin(["$host:$port"]);
        } catch (\gcf\tasks\errorJobServer $e) {
            $this->getLogger()->err(__CLASS__ . "Driver error: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->getLogger()->err(__CLASS__ . ": " . $e->getMessage());
            return null;
        }

        return $this->jobExecutor;
    }

    /**
     * Get database connections pool
     * @param string $dbName
     * @return connectionPool
     */
    public static function getDBPool(string $dbName = "")
    {
        return connectionPool::getInstance($dbName);
    }
}
