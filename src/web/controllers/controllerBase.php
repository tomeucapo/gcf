<?php

namespace gcf\web\controllers;

use gcf\ConfiguratorBase;
use gcf\database\DatabaseConnector;
use Laminas\Config\Config;
use Laminas\Log\Logger;
use stdClass;
/**
 * Class controllerBase
 *
 * This class defines controller base class. Its basic class that able to create new controllers for application.
 * This clsas not support views only for basic controller like API controllers.
 * Provides database connection context if is needed, logging context, application configuration context and basic
 * filter input class that content incoming data from client.
 */
class controllerBase
{
    protected ConfiguratorBase $configurador;

    protected DatabaseConnector $db;

    public ?Logger $logger;

    public stdClass $filtres;

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
        $this->filtres = new stdClass();
    }

    protected static function classBaseName() : string
    {
        $className = explode("\\", get_called_class());
        return array_pop($className);
    }
}