<?php

namespace gcf\web\controllers;

use app\configurador;
use gcf\ConfiguratorBase;
use gcf\database\base_dades;
use Laminas\Config\Config;
use Zend_Log;

class controllerBase
{
    protected configurador $configurador;

    protected base_dades $db;

    public $logger;

    public \stdClass $filtres;

    public $lastResult = null;
    public $lastResultType = null;
    public $lastResultOutfile = null;

    public bool $authenticated = false;

    public Config $masterConfig;

    public function __construct(ConfiguratorBase $cfg)
    {
        $this->masterConfig = $cfg->getConfig();
        $this->configurador = $cfg;
        $this->logger = $cfg->getLoggerObject();
        $this->filtres = new \stdClass();
    }

    protected static function classBaseName() : string
    {
        $className = explode("\\", get_called_class());
        return array_pop($className);
    }
}