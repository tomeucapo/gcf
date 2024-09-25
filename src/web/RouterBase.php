<?php

namespace gcf\web;

use ArgumentCountError;
use Exception;
use gcf\ConfiguratorBase;
use gcf\database\drivers\errorDatabaseAutentication;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorQuerySQL;
use gcf\web\controllers\AcceptVerbs;
use gcf\web\controllers\WSMethod;
use Laminas\Json\Json;
use ReflectionException;
use stdClass;
use Laminas\Config;

abstract class RouterBase
{
    protected string $url;
    protected string $modType;
    protected string $modName;
    protected string $funName;
    protected string $modGroupName = "";
    protected array $headers = [];

    protected Config\Config $config;

    protected ConfiguratorBase $cfg;

    private readonly array $validTypes;

    public function __construct(Config\Config $config, array $validTypes=[])
    {
        $this->url = $_SERVER["REQUEST_URI"];
        foreach (getallheaders() as $name => $value)
            $this->headers[$name] = $value;

        $this->validTypes = $validTypes;
        $this->config = $config;
    }

    protected function ParseURL(string $url) : void
    {
        $urlParts = parse_url($url, PHP_URL_PATH);
        $parts = explode("/", $urlParts);

        // Remove empty elements
        foreach($parts as $p => $part)
        {
            if (empty($part))
                unset($parts[$p]);
        }

        $moduleConf = $parts;
        if (in_array(basename($_SERVER["SCRIPT_FILENAME"]), $parts)) {
            $moduleConf = array_slice($parts, 1);
        }

        if (count($moduleConf) < 3) {
            header('HTTP/1.0 400 Bad Request', true, 400);
            exit("Bad request call!");
        }

        //error_log("# parts: ".count($moduleConf)."  ".json_encode($moduleConf));

        $this->modType = $moduleConf[0];
        $this->modName = $moduleConf[1];
        $this->funName = $moduleConf[2];

        if (count($moduleConf) > 3)
        {
            $this->modGroupName = $moduleConf[1];
            $this->modName = $moduleConf[2];
            $this->funName = $moduleConf[3];
        }

        // TODO: Its temporary if for mobile application meanwhile upgrade app
        if ($moduleConf[0] === "module")
            $this->modType = "modules";

        if (!in_array($this->modType, $this->validTypes)) {
            header('HTTP/1.0 400 Bad Request', true, 400);
            exit("Bad request call!");
        }
    }

    abstract protected function BuildRequest(string $method, StdClass $filtersObj, mixed $id, mixed $dataIn) : RouterRequestBase;

    protected function ParseRequest(): RouterRequestBase
    {
        $dataIn = null;
        $id = null;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $filtres = $_GET['filtres'] ?? "";
            $id = $_GET['id'] ?? null;
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            parse_str($_SERVER["QUERY_STRING"], $dataRequest);
            if (key_exists("filtres", $dataRequest))
                $filtres = $dataRequest["filtres"];

            if (preg_match("/^application\/json/", $this->headers["Content-Type"])) {
                $dataRaw = file_get_contents("php://input");
                $dataIn = Json::decode($dataRaw, Json::TYPE_ARRAY);
            } else if (key_exists("data", $_POST)) {
                $dataIn = $_POST["data"];
            } else
                $dataIn = $_POST;

        } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            parse_str($_SERVER["QUERY_STRING"], $dataRequest);
            if (key_exists("id", $dataRequest))
                $id = $dataRequest["id"];
            if (key_exists("filtres", $dataRequest))
                $filtres = $dataRequest["filtres"];

            $dataRaw = file_get_contents("php://input");
            if (!empty($dataRaw))
            {
                if (preg_match("/^application\/json/", $this->headers["Content-Type"]))
                    $dataIn = Json::decode($dataRaw, Json::TYPE_ARRAY);
                else
                    $dataIn = $dataRaw;
            }
        }

        $filtresObj = null;
        if (!empty($filtres)) {
            $filtresObj = Json::decode($filtres);
        }

        //new RouterRequestBase($_SERVER['REQUEST_METHOD'], $id, $filtresObj, $dataIn);

        return $this->BuildRequest($_SERVER['REQUEST_METHOD'], $filtresObj, $id, $dataIn );
    }

    private function ValidateMethod(WSMethod $rqMethod, array $attribs) : bool
    {
        foreach ($attribs as $attr)
        {
            $attrObj = $attr->newInstance();
            if ($attrObj instanceof AcceptVerbs && $rqMethod !== $attrObj->method)
                return false;
        }
        return true;
    }

    /**
     * @throws ReflectionException
     */
    private function BindArgs(RouterRequestBase $rq, array $args): array
    {
        $funcArgs = [];
        if (!empty($args))
        {
            foreach ($args as $a)
            {
                if ($a->getName() === "id" && !empty($rq->id))
                    $funcArgs[] = $rq->id;

                if ($a->getName() === "data" && !empty($rq->data)) {
                    $funcArgs[] = $rq->data;
                }
            }
        }
        return $funcArgs;
    }

    abstract protected function InitConfigurator();

    public function Run() : void
    {
        $logger = $this->cfg->getLoggerObject();

        $this->ParseURL($this->url);

        $modName = "frontal\\$this->modType\\$this->modName";
        if(!class_exists($modName))
        {
            $modName = "frontal\\$this->modType\\$this->modGroupName\\$this->modName";
            if(!class_exists($modName))
            {
                @header("HTTP/1.1 404 Not Found", true, 404);
                exit("La classe del modul $modName no existeix!");
            }
        }

        $r = new \ReflectionClass($modName);
        $isModulBase = ( preg_match("/modulBase$/", $r->getParentClass()->getName()));
        $isBasicController = ( preg_match("/controllerBase$/", $r->getParentClass()->getName()));
        $isReport = ( preg_match("/DataReportBase$/", $r->getParentClass()->getName()));

        if ( !$isModulBase && ! $isBasicController && !$isReport )
        {
            header('HTTP/1.0 400 Bad Request', true, 400);
            exit("$this->modName is not a valid controller!");
        }

        try {
            $this->cfg = ::getInstance();

            if ($isModulBase || $isReport)
            {
                $this->cfg->SetConfigBase($this->config);
                $this->sessio = Session::Initialize($this->sessionName);
            }

            $objMod = new $modName($this->cfg);
            if(!method_exists($objMod, $this->funName))
            {
                @header("HTTP/1.1 404 Not Found", true, 404);
                exit("El metode $this->funName de $this->modGroupName\\$this->modName que crida no existeix");
            }

            $requestData = $this->ParseRequest();
            if ($requestData->HasFilters())
                $objMod->filtres = $requestData->filtres;

            $method = new ReflectionMethod($objMod, $this->funName);

            // Validate if method of controller called with correct HTTP Method
            if (!$this->ValidateMethod($requestData->method, $method->getAttributes()))
            {
                header('HTTP/1.0 405 Method Not Allowed', true, 405);
                exit("Method Not Allowed!");
            }

            // Binding method arguments to request
            $arguments = $this->BindArgs($requestData, $method->getParameters());

            // Call controller method
            $result = call_user_func_array([$objMod, $this->funName], $arguments);

        } catch (errorDatabaseAutentication $e) {
            header("HTTP/1.1 401 Unauthorized", true, 401);
            if ( $logger !== null )
                $logger->err("[$this->modName::$this->funName] {$this->sessio->user} Database autentication error: ".$e->getMessage());
            else error_log("[$this->modName::$this->funName] {$this->sessio->user} SQL Error: ".$e->getMessage());
            header("Content-Type: application/json; charset=utf-8");
            exit(Json::encode(["msgError" => "Usuari o contrasenya incorrectes", "detail" => $e->getMessage()]));
        } catch (errorDatabaseConnection $e) {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            error_log("[$this->modName::$this->funName] {$this->sessio->user} Database connection error: ".$e->getMessage());
            header("Content-Type: application/json; charset=utf-8");
            exit(Json::encode(["msgError" => utf8_encode($e->getMessage()), "detail" => ""]));
        } catch (errorQuerySQL $e) {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            if ($logger !== null)
                $logger->err("[$this->modName::$this->funName] {$this->sessio->user} SQL Error: " . $e->getMessage() . " SQL Sentence: " . $e->getSQLSentence());
            else error_log("[$this->modName::$this->funName] {$this->sessio->user} SQL Error: " . $e->getMessage() . " SQL Sentence: " . $e->getSQLSentence());
            exit(Json::encode(["msgError" => utf8_encode($e->getMessage()), "detail" => $e->getSQLSentence()]));
        } catch (ArgumentCountError $e) {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            if ($logger !== null)
                $logger->err("[$this->modName::$this->funName] {$this->sessio->user} Error: " . $e->getMessage());
            else error_log("[$this->modName::$this->funName] {$this->sessio->user} Error: " . $e->getMessage());
            exit(Json::encode(["msgError" => utf8_encode($e->getMessage()), "detail" => $e->getTraceAsString()]));
        } catch (Exception $e) {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            if ( $logger !== null )
                $logger->err("[$this->modName::$this->funName] {$this->sessio->user} General error: ".$e->getMessage()."\n".$e->getTraceAsString());
            else error_log("[$this->modName::$this->funName] {$this->sessio->user} General error: ".$e->getMessage()."\n".$e->getTraceAsString());
            exit(Json::encode(["msgError" => $e->getMessage(), "detail" => $e->getTraceAsString()]));
        }
    }
}