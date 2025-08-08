<?php
/**
 * ConfiguradorBase.php
 * Main configurator base class that configures all application resources as DB Connections, Caches, Loggers, ...
 */

namespace gcf;

use Exception;
use gcf\cache\cacheDriverError;
use gcf\cache\cachePlugin;
use gcf\cache\dummyPlugin;
use gcf\database\DatabaseConnector;
use gcf\database\errorDriverDB;
use gcf\tasks\errorJobServer;
use gcf\tasks\taskPlugin;
use gcf\web\templates\templateEngine;
use Laminas;
use Laminas\Config\Config;
use Laminas\Log\Logger;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

abstract class ConfiguratorBase
{
    public array $dirs = [];

    public templateEngine $tmpl;

    /**
     * @var array
     */
    private array $tmplClasses = [];

    public ?DatabaseConnector $db = null;

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
     * @throws Exception
     */
    private function initDBMain() : void
    {
        try {
            $dbMain = $this->config->general->maindb;
            $dbPool = ConnectionPool::getInstance();
            $this->db = $dbPool->$dbMain->getConnection();
        } catch (errorDriverDB $e) {
            error_log($e->getMessage());
            throw new Exception("DB Error: " . $e->getMessage());
        }
    }

    private function InitLogger(string $loggerName) : Laminas\Log\Logger
    {
        $logOptions = $this->config->logging->$loggerName;

        $writer = new Laminas\Log\Writer\Stream($logOptions->log->writerParams->stream);
        $filter = new Laminas\Log\Filter\Priority($logOptions->log->filterParams->priority, $logOptions->log->filterParams->operator);
        $writer->addFilter($filter);

        $logger = new Laminas\Log\Logger();
        $logger->addWriter($writer);

        return $logger;
    }

    /**
     * Initialize all loggers configured into configuration file
     */
    private function initLoggers() : void
    {
        $loggers = explode(",", $this->config->logging->loggers);
        foreach ($loggers as $loggerName) {
            $this->serviceManager->setService("logger." . $loggerName, $this->InitLogger($loggerName)); //Zend_Log::factory($logOptions->toArray()));
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
     * @throws Exception
     */
    public function setConfig(Config $config) : void
    {
        $this->SetConfigBase($config);
        $this->initLoggers();

        if ($this->config->general->maindb !== null)
            $this->initDBMain();

        if (!$config->general->template_engines)
            throw new Exception("There not defined template_engines into configuration file!");

        foreach (explode(",", $config->general->template_engines) as $engineName)
        {
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
     * @return ?Logger
     */
    public function getLoggerObject(?string $loggerName = null) : ?Logger
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
                if ($objLogger instanceof Logger)
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
     * @param int|null $dbIndex
     * @return cachePlugin
     */
    public function getCache(?int $dbIndex=null): cachePlugin
    {
        if ($this->cache instanceof cachePlugin)
            return $this->cache;

        $type = $this->config->cache->type;
        $host = $this->config->cache->host;
        $port = $this->config->cache->port;

        if ($dbIndex === null && !empty($this->config->cache->dbindex))
            $dbIndex = $this->config->cache->dbindex;

        $classPlugin = "\\gcf\\cache\\$type" . "Plugin";
        if (!class_exists($classPlugin))
            return new cache\dummyPlugin();

        $cnx = new stdClass();
        $cnx->host = $host;
        $cnx->port = $port;
        try {
            /** @var cachePlugin $classPlugin */
            $this->cache = new $classPlugin([$cnx], $dbIndex);
        } catch (cacheDriverError $e) {
            $this->getLoggerObject()->err(__CLASS__ . " Cache driver error: " . $e->getMessage());
            return new dummyPlugin();
        } catch (Exception $e) {
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
            $this->getLoggerObject()->err("El driver $classPlugin no existeix!");
            return null;
        }
        try {
            /** @var taskPlugin $classPlugin */
            $this->jobExecutor = new $classPlugin(["$host:$port"]);
        } catch (errorJobServer $e) {
            $this->getLoggerObject()->err(__CLASS__ . "Driver error: " . $e->getMessage());
            return null;
        } catch (Exception $e) {
            $this->getLoggerObject()->err(__CLASS__ . ": " . $e->getMessage());
            return null;
        }

        return $this->jobExecutor;
    }

    /**
     * Get database connections pool
     * @param string $dbName
     * @return ConnectionPool
     */
    public static function getDBPool(string $dbName = "") : ConnectionPool
    {
        return ConnectionPool::getInstance($dbName);
    }

    abstract public function InitPermissions(int $userId, string $moduleName) : void;
}
