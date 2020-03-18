<?php
/****************************************************************************************
 * clienttime_cp6000.php
 * Classe per gestionar la connexi� a les terminals CP6000 que treballen directament
 * com a webservices REST per HTTP.
 *
 * Created......: 02/11/2012
 * Last modified: 13/08/2014
 * Author.......: Tomeu Cap� Cap� 2013 (C)
 *
 * Alc�dia Mar�tima S.A. 2002/13 (C)
 *****************************************************************************************/

include_once "fingerConvert.php";
include_once "clientTimeBase.php";

class responseError extends Exception
{
}

;

class processResponseError extends Exception
{
}

;

class sendRequestError extends Exception
{
}

;

use Laminas\Http\Client;
use Laminas\Http\Request;

class clientTimeCP6000 extends clientTimeBase
{
    const DEBUG = true;
    const LIST_ROOT = 0;
    const FIELD_ROOT = 1;

    public $campsRes;
    private $preAuth;
    private $urlBase, $user, $passwd;
    private $id, $command;

    /**
     * @var DOMNode
     */
    private $rootXML;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var DOMDocument
     */
    private $domRq;

    /**
     * @var array
     */
    private $listRequests;

    // Taules de conversions entre noms de camps i valors de CP6000 a CP5000

    private static $cnvCmds = array("getconfig" =>
        ["biomode" => "fingerprint_punch_mode",
            "societyname" => "societycode",
            "language" => "language",
            "tempfmt" => "temperatureformat",
            "datefmt" => "dateformat",
            "cardfmt" =>
                ["length" => "cardformat_cardlength",
                    "pos_scode" => "cardformat_societycodeposition",
                    "len_scode" => "cardformat_societycodelength",
                    "pos_pcode" => "cardformat_personalcodeposition",
                    "len_pcode" => "cardformat_personalcodelength",
                    "pos_ctype" => "cardformat_cardtypeposition",
                    "len_ctype" => "cardformat_cardtypelength",
                    "pos_exp" => "cardformat_cardexpirationposition",
                    "len_exp" => "cardformat_cardexpirationlength",],
            "attreaders" => null,
            "acctimes" => null,
            "keybmode" => "keyboard_activation",
            "f4key" => "keyboard_F4key"
        ],
        "getalltransactions" =>
            array("id" => "personal_code",
                "rn" => "reader_number",
                "incid" => "incidence_code",
                "type" => "transaction_type",
                "mode" => "source"),
        "getnewtransactions" =>
            array("id" => "personal_code",
                "rn" => "reader_number",
                "incid" => "incidence_code",
                "type" => "transaction_type",
                "mode" => "source"));

    private static $cnvValues = array("type" =>
        array("T" => "attendance",
            "A" => "access",
            "P" => "production",
            "B" => "workingtimebeginning"),
        "mode" =>
            array("F" => "fingerprintreader",
                "K" => "keyboard",
                "C" => "cardreader"));

    private static $cnvFormat = array("incid" => "%06d",
        "id" => "%04d");

    // Equivalencia de les comandes de CP5000 a crides REST del CP6000

    private static $L_FUNCTIONS = array("systeminfo" => array("sysinfo", Request::METHOD_GET),
        "gettime" => array("clock", Request::METHOD_GET),
        "settime" => array("clock", Request::METHOD_PUT),
        "getconfig" => array("config", Request::METHOD_GET),
        "sendincidences" => array("incidences", Request::METHOD_POST),
        "deleteincidences" => array("incidences", Request::METHOD_DELETE),
        "getnewtransactions" => array("transactions/new", Request::METHOD_GET),
        "getalltransactions" => array("transactions", Request::METHOD_GET),
        "deletetransactions" => array("transactions", Request::METHOD_DELETE),
        "biogetfingerprints" => array("fingerprints", Request::METHOD_GET),
        "biosendfingerprints" => array("fingerprints", Request::METHOD_POST),
        "biodeleteusers" => array("fingerprints", Request::METHOD_DELETE),
        "biodeleteallusers" => array("fingerprints", Request::METHOD_DELETE)
    );

    private static $L_ERRCODES = array(1 => "OPERATION_FAILED",
        "BAD_ARGUMENTS",
        "OPERATION_NOT_PERFORM",
        "OUT_OF_MEMORY");



    /**
     * clientTimeCP6000 constructor.
     * @param string $srv_ip Server Time IP address
     * @param $srv_port
     * @param string $user
     * @param string $passwd
     */
    public function __construct($srv_ip, $srv_port, $user = '', $passwd = '')
    {
        if ($srv_ip)
            $this->urlBase = "http://$srv_ip:$srv_port";

        $this->user = $user;
        $this->passwd = $passwd;

        $this->buff = '';
        $this->errcode = '';
        $this->status = '';
        $this->listResponses = [];
        $this->listRequests = [];

        // Habilita/deshabilita la pre-autenticacio abans de cada peticio
        $this->preAuth = false;

        $this->httpClient = new Client();
        $this->initRequest();
    }

    private function initRequest()
    {
        $this->domRq = new DOMDocument('1.0', 'ISO-8859-1');
        $this->domRq->formatOutput = true;
        $this->rootXML = $this->domRq->appendChild($this->domRq->createElement("request"));
    }

    private function auth()
    {
        if (!empty($this->user) && !empty($this->passwd))
            $this->httpClient->setOptions(array("httpauth" => "{$this->user}:{$this->passwd}",
                "httpauthtype" => HTTP_AUTH_DIGEST));
    }

    public function open()
    {
        //$this->auth();
    }

    /**
     * @param $cmd
     * @param string $xml
     * @param string $idUrl
     * @throws responseError
     * @throws Exception
     */
    private function sendRequest($cmd, $xml = "", $idUrl = "")
    {
        $resource = clientTimeCP6000::$L_FUNCTIONS[$cmd][0];
        $method = clientTimeCP6000::$L_FUNCTIONS[$cmd][1];

        $request = new Request();
        $request->setUri($this->urlBase . '/' . $resource . $idUrl);
        $request->getHeaders()->addHeaders([
            'User-Agent' => 'ClientTime CP6000',
            'Content-Type' => 'text/xml']);

        if ($this->preAuth)
        {
            if ($method == Request::METHOD_POST || $method == Request::METHOD_PUT)
                $request->setMethod(Request::METHOD_HEAD);

            $this->httpClient->send($request);
            $this->auth();
        }

        if (self::DEBUG) echo "[ TX ] $method " . $request->getUriString() . "\n";
        if ($xml)
        {
            if (self::DEBUG) echo "[ TX ] $method $xml\n";
            $request->setContent($xml);
        }

        $request->setMethod($method);
        try {
            $response = $this->httpClient->send($request);
            $resCode = $response->getStatusCode();
            $resBody = $response->getBody();
        } catch (Exception $ex) {
            $this->lastError = $ex->getMessage();
            if (self::DEBUG) echo "[ CRITICAL " . __CLASS__ . " ] " . $ex->getMessage() . "\n";
            throw $ex;
        }

        if (self::DEBUG) echo "[ RX ] $resCode $resBody\n";

        if ($resCode != 200)
            throw new responseError(trim($resBody), $code = $resCode);

        $this->buff = $resBody;
    }

    private function mountRequest(DOMNode $root, $l_args)
    {
        if (!is_array($l_args))
            return;

        foreach ($l_args as $name => $value) {
            $name = ($name == "text") ? "description" : $name;

            if ($name != "last")
                $root->appendChild($this->domRq->createElement($name, $value));
        }
    }

    // Envia una trama XML al servidor, i agafam la resposta

    /**
     * @param string $type
     * @param string $cmd
     * @param null $l_args
     * @return mixed
     * @throws sendRequestError
     */
    public function sendCommand($type, $cmd, $l_args = null)
    {
        if (strlen($cmd) == 0) return null;

        // Si es una comanda de servidor en aquest cas l'obviam
        // i si es la comanda de connexio ens guardam la IP i el port

        if ($type == "SERVER") {
            if ($cmd == "connectnetwork")
                $this->urlBase = "http://" . $l_args["ip"] . ":" . $l_args["port"];

            return 0;
        }

        if (!array_key_exists($cmd, clientTimeCP6000::$L_FUNCTIONS))
            throw new sendRequestError("Function not implemented");

        $this->buff = '';
        $this->command = $cmd;
        $idUrl = '';
        $xml = '';

        $method = clientTimeCP6000::$L_FUNCTIONS[$cmd][1];

        $this->initRequest();        // Inicialitzam la request, el DOM amb un request buid
        if (is_array($l_args)) {
            if (array_key_exists("id", $l_args) && ($method == Request::METHOD_GET || $method == Request::METHOD_DELETE)) {
                $idUrl = "/" . $l_args["id"];
                $this->id = $l_args["id"];
            } else {
                $this->mountRequest($this->rootXML, $l_args);
                $xml = $this->domRq->saveXML();
            }
        }

        try {
            $this->sendRequest($cmd, $xml, $idUrl);

            if (strlen($this->buff) > 0) {
                $this->listResponses = [];
                if ($this->processResponse() < 0)
                    return -3;
            }
            return 0;
        } catch (Exception $ex) {
            return -2;
        }
    }

    /**********************************************************************
     * M�todes per enviar blocs de comandes al servidor
     **********************************************************************/

    public function clearCommandsBlock()
    {
        //$this->domRq->removeChild($this->rootXML);
        //$this->rootXML = $this->domRq->appendChild($this->domRq->createElement("list"));

        $this->command = "";
        $this->listRequests = array();
    }

    /**
     * @param string $cmd
     * @param null $l_args
     * @return mixed|void
     * @throws sendRequestError
     */
    public function addCommandToBlock($cmd, $l_args = null)
    {
        if (!array_key_exists($cmd, clientTimeCP6000::$L_FUNCTIONS))
            throw new sendRequestError("Function not implemented");

        $method = clientTimeCP6000::$L_FUNCTIONS[$cmd][1];

        $this->command = $cmd;

        if ($method == Request::METHOD_POST || $method == Request::METHOD_PUT) {
            $this->domRq = new DOMDocument('1.0', 'ISO-8859-1');
            $this->domRq->formatOutput = true;
            $nodeBlock = $this->domRq->appendChild($this->domRq->createElement("request"));
        } else
            $nodeBlock = $this->rootXML->appendChild($this->domRq->createElement("i"));

        // Determinam si ens pasen un array que defineixen les dades a enviar
        // si es un array es que es un conjunt de par�metres no un string

        if (is_array($l_args)) {
            // Eliminam la clau last ja que el CP6000 no el necessita
            if (array_key_exists("last", $l_args))
                unset($l_args["last"]);

            // Si es la comanda per enviar empremtes convertim el xurro hex del CP5000
            // a un XML que reconeix el CP6000

            if ($cmd == "biosendfingerprints") {
                try {
                    $cnvFinger = new fingerPrintConvert();
                    $tmpls = $cnvFinger->toCP6000($l_args);
                    if (!empty($tmpls))
                        $cnvFinger->getXMLNode($nodeBlock, $this->domRq);
                } catch (Exception $ex) {
                    if (self::DEBUG) echo "[ ERROR " . __CLASS__ . " ] " . $ex->getMessage() . "\n";
                }
            } else
                $this->mountRequest($nodeBlock, $l_args);
        }

        // TODO: Revisar aquest condicional, podria entrar en un cas que no funcioni com toca

        if ($method == Request::METHOD_POST || $method == Request::METHOD_PUT)
            $this->listRequests[] = $this->domRq->saveXML();
        else {
            if (is_string($l_args))
                $nodeBlock->appendChild($this->domRq->createTextNode($l_args));            // TODO: Revisar aquest cas!
        }
    }

    public function sendCommandsBlock()
    {
        $this->buff = '';
        $result = 0;

        foreach ($this->listRequests as $xmlRequest) {
            try {
                $this->sendRequest($this->command, $xmlRequest);
                if (strlen($this->buff) > 0) {
                    if ($this->processResponse() < 0) {
                        $result = -3;
                        break;
                    }
                }
            } catch (Exception $ex) {
                $result = -2;
                break;
            }
        }

        return $result;
    }


    /**********************************************************************
     * Extreu els camps d'una resposta
     *
     * @param DOMNamedNodeMap $attributes
     * @return array
     */

    private function extractFields(DOMNamedNodeMap $attributes)
    {
        $l_fields = [];

        foreach ($attributes as $attr)
            $l_fields[$attr->name] = $attr->value;

        return $l_fields;
    }

    /**
     * convertFields
     * Converteix el noms dels camps i els valors si es necessari, per que la capa
     * superior no tengui problemes, inicialment la classe terminal estava pensada per les
     * terminals CP-5000 i aquestes tenen camps amb noms diferents per aixo feim la traduccio..
     * @param array $camps
     * @param bool $last
     * @param string|null $subfield
     * @return array
     */

    private function convertFields($camps, $last = false, $subfield = null)
    {
        if (!array_key_exists($this->command, clientTimeCP6000::$cnvCmds))
            return $camps;

        $convertFields = clientTimeCP6000::$cnvCmds[$this->command];

        if ($subfield !== null) {
            if (!array_key_exists($subfield, $convertFields))
                return $camps;
            $convertFields = $convertFields[$subfield];
        }

        if ($convertFields === null)
            return [];

        $nousCamps = array();
        foreach ($camps as $nomCamp => $valor) {
            if (!is_array($valor)) {
                if (array_key_exists($nomCamp, clientTimeCP6000::$cnvFormat)) {
                    $valorNou = sprintf(clientTimeCP6000::$cnvFormat[$nomCamp], $valor);
                    $valor = $valorNou;
                }
            } else {
                $nousCamps = array_merge($nousCamps, $this->convertFields($valor, false, $nomCamp));
                continue;
            }

            if (array_key_exists($nomCamp, $convertFields)) {
                if (array_key_exists($nomCamp, clientTimeCP6000::$cnvValues))
                    $nouValor = clientTimeCP6000::$cnvValues[$nomCamp][$valor];
                else $nouValor = $valor;

                $nousCamps[$convertFields[$nomCamp]] = $nouValor;
            } else $nousCamps[$nomCamp] = $valor;
        }

        if ($subfield === null)
            $nousCamps["last"] = $last ? "true" : "false";

        return $nousCamps;
    }

    /**
     * Obte tots els regitres d'una peticio
     * @param $subRoot
     * @param int $rootType
     * @return array
     */

    private function getNodes($subRoot, $rootType = self::LIST_ROOT)
    {
        $camps = [];
        $registres = [];

        $i = 0;
        $nRegistres = $subRoot->childNodes->length;

        /** @var DOMNode $node */
        foreach ($subRoot->childNodes as $node)
        {
            $i++;
            if ($rootType == self::LIST_ROOT && $node->nodeName != 'i')
                continue;

            // Si es un registre compost de camps extreim cada un dels camps
            if ($node->hasChildNodes() && $node->childNodes->length > 1) {
                $camps = array();
                foreach ($node->childNodes as $subNode)
                    $camps[$subNode->nodeName] = $subNode->nodeValue;
                $registres[] = $this->convertFields($camps, ($nRegistres == $i));
            } else {
                if ($rootType == self::FIELD_ROOT && $node->nodeName != 'i' && $node->nodeName != "#comment")
                    $camps[$node->nodeName] = $node->nodeValue;
                else
                    $camps[] = $node->nodeValue;
            }
        }

        if (count($registres) > 0)
            return $registres;

        if ($this->id && $this->command == "biogetfingerprints") {
            $cnvFinger = new fingerPrintConvert();
            return array($cnvFinger->toCP5000($this->id, count($camps), $camps));
        }

        return $camps;
    }

    /**
     * @return int
     * @throws processResponseError
     */
    private function processResponse()
    {
        if (trim($this->buff) == '')
            return -3;

        if (!($domResponse = DOMDocument::loadXML($this->buff)))
            throw new processResponseError("Error resposta XML invalida");

        if (!$domResponse->hasChildNodes())
            throw new processResponseError("Error resposta XML buida");

        $root = $domResponse->documentElement;
        $this->campsRes = [];
        $result = [];

        /** @var DOMNode $node */
        foreach ($root->childNodes as $node) {
            switch ($node->nodeName) {
                case "list":
                    $this->listResponses = $this->getNodes($node, self::LIST_ROOT);
                    break;

                case "result":
                    $result = $this->extractFields($node->attributes);
                    break;

                default:
                    if ($node->hasChildNodes() && $node->childNodes->length > 1) {
                        $this->campsRes[$node->nodeName] = $this->getNodes($node, self::FIELD_ROOT);
                    } else $this->campsRes[$node->nodeName] = $node->nodeValue;
                    break;
            }
        }

        if (!empty($this->campsRes)) {
            $campsConv = $this->convertFields($this->campsRes, true);
            $this->campsRes = $campsConv;
        }

        if ($result["valid_request"] == "false")
            return -1;

        $code = (int)$result["code"];
        $retval = -1;

        if ($code < 0)
            $this->errcode = $this->campsRes["errcode"] = "UNKNOWN_ERROR";
        else if ($code >= 1)
            $this->errcode = $this->campsRes["errcode"] = clientTimeCP6000::$L_ERRCODES[$code];
        else {
            $this->errcode = "OK";
            $retval = 0;
        }

        return $retval;
    }


    public function close()
    {
    }
}

