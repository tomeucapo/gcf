<?php
/**
 * ConfiguradorBase.php
 * Main configurator base class that configures all application resources as DB Connections, Caches, Loggers, ...
 */

namespace gcf;

use gcf\cache\cacheDriverError;
use gcf\cache\cachePlugin;
use gcf\cache\dummyPlugin;
use gcf\database\base_dades;
use gcf\database\errorDriverDB;
use gcf\tasks\errorJobServer;
use gcf\tasks\taskPlugin;
use gcf\web\templates\templateEngine;
use Laminas;
use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zend_Log;

require_once "Zend/Log.php";

abstract class ConfiguratorBase
{
    public array $dirs = [];

    public web\templates\templateEngine $tmpl;

    /**
     * @var array
     */
    private array $tmplClasses = [];

    public base_dades $db;

    protected Config $config;

    protected ?cache\cachePlugin $cache = null;

    protected ?taskPlugin $jobExecutor = null;

    /**
     * @var string action or module name
     */
    protected string $accio;

    /**
     * @var array
     */
    private static array $_instances = [];

    /**
     * @var ServiceManager
     */
    private ServiceManager $serviceManager;

    /**
     * ConfiguratorBase constructor.
     *
     */
    protected function __construct()
    {
        $this->serviceManager = new ServiceManager();
	    $this->accio = "";
    }

    /**
     * Get configuration instance
     * @return ConfiguratorBase
     */
    public static function getInstance() : ConfiguratorBase
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
    private function initDBMain() : void
    {
        try {
            $dbMain = $this->config->general->maindb;
            $dbPool = connectionPool::getInstance();
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
    private function initLoggers() : void
    {
        try {
            $loggers = explode(",", $this->config->logging->loggers);
            foreach ($loggers as $loggerName) {
                $logOptions = $this->config->logging->$loggerName;
                $this->serviceManager->setService("logger." . $loggerName, Zend_Log::factory($logOptions->toArray()));
            }
        } catch (\Zend_Log_Exception $e) {
            error_log("Logger init error: " . $e->getMessage());
        }
    }

    public function SetConfigBase(Config $config) : void
    {
        $this->config = $config;

        $paths = $config->paths;
        $this->dirs = ["root" => $paths->path->root,
            "app" => $paths->path->app,
            "appbase" => $paths->path->appbase,
            "imatges" => $paths->path->imatges,
            "include" => $paths->path->include,
            "templates" => $paths->path->templates];
    }

    /**
     * Set application configuration from .ini file
     * @param Config $config
     * @throws \Exception
     */
    public function setConfig(Config $config) : void
    {
        $this->SetConfigBase($config);
        $this->initLoggers();
        $this->initDBMain();

        if (!$config->general->template_engines)
            throw new \Exception("There not defined template_engines into configuration file!");

        foreach (explode(",", $config->general->template_engines) as $engineName) {
            $engineName = trim($engineName);
            $engineClassName = "gcf\\web\\templates\\" . $engineName;

            /** @var templateEngine $tmplObj */
            $tmplObj = new $engineClassName();
            $tmplObj->setBasedir($this->dirs["templates"]);
            $tmplObj->setAssetsdir($config->paths->path->imatges);
            $this->tmplClasses[$engineName] = $tmplObj;
        }

        $this->tmpl = $this->tmplClasses["twigEngine"];
    }

    /**
     * Get template engine object
     * @param string $engineName
     * @return templateEngine
     */
    public function getTmplEngine(string $engineName) : templateEngine
    {
        return $this->tmplClasses[$engineName];
    }

    /**
     * Get logger instance
     * @param string|null $loggerName
     * @return ?Zend_Log
     */
    public function getLoggerObject(?string $loggerName = null) : ?Zend_Log
    {
            if ($loggerName === null) {
                $loggerName = "logger." . $this->accio;
            } else $loggerName = "logger.$loggerName";

            if (!$this->serviceManager->has($loggerName))
                $loggerName = "logger.general";

            if (!$this->serviceManager->has($loggerName))
                return null;

            try {
                $objLogger = $this->serviceManager->get($loggerName);
                if ($objLogger instanceof Zend_Log)
                    return $objLogger;
            } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
                error_log("Can't get logger $loggerName: ".$e->getMessage());
            }

            return null;
    }

    /**
     * Get application configuration object
     * @return Config
     */
    public function getConfig() : Config
    {
        return $this->config;
    }

    /**
     * Get cache connection object
     * @return cachePlugin
     */
    public function getCache(): cachePlugin
    {
        if ($this->cache instanceof cachePlugin)
            return $this->cache;

        $dbindex = null;
        $type = $this->config->cache->type;
        $host = $this->config->cache->host;
        $port = $this->config->cache->port;
        if (!empty($this->config->cache->dbindex))
            $dbindex = $this->config->cache->dbindex;

        $classPlugin = "\\gcf\\cache\\$type" . "Plugin";
        if (!class_exists($classPlugin))
            return new cache\dummyPlugin();

        $cnx = new \stdClass();
        $cnx->host = $host;
        $cnx->port = $port;
        try {
            /** @var cachePlugin $classPlugin */
            $this->cache = new $classPlugin([$cnx], $dbindex);
        } catch (cacheDriverError $e) {
            $this->getLoggerObject()->err(__CLASS__ . " Cache driver error: " . $e->getMessage());
            return new dummyPlugin();
        } catch (\Exception $e) {
            $this->getLoggerObject()->err(__CLASS__ . " Cache general error: " . $e->getMessage());
            return new dummyPlugin();
        }

        return $this->cache;
    }

    /**
     * Get job server connection object
     * @return ?taskPlugin
     */
    public function getJobExecutor() : ?taskPlugin
    {
        if ($this->jobExecutor instanceof taskPlugin)
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
            /** @var taskPlugin $classPlugin */
            $this->jobExecutor = new $classPlugin(["$host:$port"]);
        } catch (errorJobServer $e) {
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
