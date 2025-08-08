<?php
namespace gcf;

use Exception;
use gcf\database\ConnectionDb;
use Laminas\Config;

class Environment
{
    public readonly string $appName;
    public readonly string $envName;
    public readonly string $appSessionName;

    public readonly Config\Config $config;

    public readonly string $dbMain;

    public readonly string $envID;
    private readonly string $pathConf;

    public ?ConnectionPool $dbPool = null;

    public readonly ?ConfiguratorBase $appCfg;

    private static Environment $_instance;

    /**
     * Constructor that read environment variables and parse application configuration properties
     *
     * @param string $appName Application name
     * @throws connectionTypeError
     * @throws Exception
     */
    private function __construct(string $appName)
    {
        if (empty(getenv("APPS_ENVIRONMENT_ID")))
            throw new Exception("Falta configurar la variable d'entorn APPS_ENVIRONMENT_ID");

        $this->envName = getenv("APPS_ENVIRONMENT");
        $this->envID = getenv("APPS_ENVIRONMENT_ID");
        $this->pathConf = getenv("APPS_ENVIRONMENT_CONF");
        $this->appName = $appName;

        $this->appSessionName = $appName."_".$this->envID;

        $this->ParseConfig($this->pathConf."/$appName/properties_{$this->envID}.ini");
        $this->dbPool = $this->InitDBConnections();
    }

    /**
     * Get Environment instance
     * @param string|null $appName
     * @return Environment
     * @throws Exception
     */
    public static function getInstance(?string $appName=null) : Environment
    {
        if (!isset(self::$_instance) && $appName !== null)
            self::$_instance = new Environment($appName);

        return self::$_instance;
    }

    /**
     * Parse application properties ini file
     * @throws Exception
     */
    private function ParseConfig(string $cfgFile) : void
    {
        try {
            $configObj = new Config\Reader\Ini();
            $configParameters = $configObj->fromFile($cfgFile);
            $this->config = new Config\Config($configParameters);
        } catch(Config\Exception\RuntimeException $e) {
            throw new \Exception("No puc llegir el fitxer de configuració: ".$e->getMessage());
        }
    }

    /**
     * Initialize all database connection proper
     * @return ConnectionPool|null
     * @throws connectionTypeError
     */
    public function InitDBConnections() : ?ConnectionPool
    {
        if ($this->config->general->databases === null)
            return null;

        // Inicialitzem les configuracions de les bases de dades
        $dbs = explode(",", $this->config->general->databases);
        if (empty($dbs))
            return null;

        $this->dbMain = $this->config->general->maindb;

        // Pre-inicialitza les connexions de BBDD que hi ha definides al fitxer de configuració
        $dbPool = ConnectionPool::getInstance($this->config->general->maindb);
        foreach($dbs as $dbname)
        {
                $dbPool->$dbname = new ConnectionDb($this->config->$dbname->database);
        }

        return $dbPool;
    }

    public function ApplicationConfigurator(ConfiguratorBase $cfg) : void
    {
        $this->appCfg = $cfg;
    }

    /**
     * @throws Exception
     */
    public function GetAppConfigurator() : ConfiguratorBase
    {
        if ($this->appCfg === null)
            throw new Exception("You not initialized application configurator in Environment!");
        return $this->appCfg;
    }
}
