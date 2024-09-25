<?php

namespace gcf\tests\web;

use ArgumentCountError;
use gcf\database\drivers\errorDatabaseAutentication;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorQuerySQL;
use Exception;
use gcf\database\models\noPrimaryKey;
use gcf\Environment;
use gcf\session\laminas\SessionManager;
use gcf\web\controllers\AcceptVerbs;
use gcf\web\controllers\WSMethod;
use gcf\web\RouterBase;
use gcf\web\RouterRequestBase;
use Laminas\Json\Json;
use Laminas\Config;
use ReflectionMethod;
use stdClass;

class Router extends RouterBase
{
    private string $sessionName;

    private bool $JSONMode;

    private SessionManager $sessio;

    public function __construct(string $sessionName,   Config\Config $config)
    {
        parent::__construct($config, ["reports", "modules", "gui"]);

        $JSONMode = $_GET["JSONMode"] ?? false;
        $this->JSONMode = $JSONMode || preg_match("/^application\/json/", $this->headers["Content-Type"]);
        $this->sessionName = $sessionName;
    }

    /**
     * @throws errorQuerySQL
     * @throws noPrimaryKey
     * @throws Exception
     */
    private function InitConfiguration(string $moduleName) : void
    {
        $this->cfg->setConfig($this->config);
        $this->cfg->InitPermissions($this->sessio->userInfo["UID"], $moduleName);
    }


    public function Run() : void
    {
        try {


            if ($this->modName !== "login")
                $this->InitConfiguration($this->modName);
            else {
                $this->cfg = configurador::getInstance();
                $this->cfg->SetConfigBase($this->config);
            }

            Environment::getInstance()->ApplicationConfigurator($this->cfg);



            // If direct result from method then get it and decode if is necessary
            if ($result !== null)
            {
                if ($this->JSONMode || ($this->sessio->jsonmode === "YES"))
                {
                    header("Content-Type: application/json; charset=utf-8");
                    echo Json::encode($result, true);
                }
                else echo $result;
                return;
            }

            // Otherwise try to get lastResult variable that contains response data
            if ($objMod->lastResult)
            {
                if ($objMod->lastResultType)
                {
                    header("Content-type: " . $objMod->lastResultType);
                    if ($objMod->lastResultOutfile !== null)
                        header("Content-Disposition: attachment; filename=\"$objMod->lastResultOutfile\"");
                }
                echo $objMod->lastResult;
                return;
            }


    }

    protected function BuildRequest(string $method, stdClass $filtersObj, mixed $id, mixed $dataIn): RouterRequestBase
    {
        // TODO: Implement BuildRequest() method.
    }

    protected function InitConfigurator()
    {
        // TODO: Implement InitConfigurator() method.
    }
}
