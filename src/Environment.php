<?php
namespace gcf;

use Exception;
use gcf\database\connectionDb;
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

    public ?connectionPool $dbPool = null;

    private static Environment $_instance;

    /**
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
     * Get configuration instance
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
     * @throws Exception
     */
    private function ParseConfig(string $cfgFile) : void
    {
        try {
            $configObj = new Config\Reader\Ini();
            $configParameters = $configObj->fromFile($cfgFile);
            $this->config = new Config\Config($configParameters);
        } catch(Config\Exception\RuntimeException $e) {
            throw new \Exception("No puc llegir la configuracio: ".$e->getMessage());
        }
    }

    /**
     * @return connectionPool|null
     * @throws \connectionTypeError
     */
    public function InitDBConnections() : ?connectionPool
    {
        if ($this->config->general->databases === null)
            return null;

        // Inicialitzam les configuracions de les bases de dades
        $dbs = explode(",", $this->config->general->databases);
        if (empty($dbs))
            return null;

        $this->dbMain = $this->config->general->maindb;

        // Preinicialitza les connexions de BBDD que hi ha definides al fitxer de configuració
        $dbPool = connectionPool::getInstance($this->config->general->maindb);
        foreach($dbs as $dbname)
        {
                $dbPool->$dbname = new connectionDb($this->config->$dbname->database);
        }

        return $dbPool;
    }
}
