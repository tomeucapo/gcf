<?php
/**
 * wsBase.php
 * Classe base d'un webservice, tots els moduls s'implementen en base a aquestsa clase
 * 
 * @author Tomeu Capó <tomeucapo@me.com>
 */

use gcf\web\ws\JSONEncodingError;

include_once "interfaces/wsInterface.php";
include_once "ws/dataResponse.php";

class methodNotAllowed extends Exception {}

class paramNotValid extends Exception {}

/**
 * Classe abstracte de lo que definim com a webservice, aquesta classe no s'intancia directament
 * si no que feim clases derivades d'ella.
 */

abstract class webServiceBase implements webservice
{
         const JSON_TYPE = 1;
         const RAW_TYPE = 2;
         const PDF_TYPE = 3;
         const XML_TYPE = 4;
         const HTML_TYPE = 5;
         
         private $result;
         private $typeOut;
         private $runTime;
        
         protected $validParams;
         protected $validMethods;
         protected $dataRequest, $dataRawRequest;
         protected $method;
 
         protected function __construct($validParams, $validMethods, $type = self::JSON_TYPE)
         {
                $this->result = new dataResponse(null);
                $this->dataRequest = array(); 
                $this->dataRawRequest = "";
                $this->runTime = 0.0;                
                $this->typeOut = $type;
                
                $this->method = $_SERVER['REQUEST_METHOD'];
                
                if (!in_array($this->method, $validMethods))
                   throw new methodNotAllowed("Method {$this->method} not allowed");

                $this->getData();
                
                // Si dentro del request hay el modulo lo sacamos del dataRequest
                if (array_key_exists("modulo", $this->dataRequest))
                    unset($this->dataRequest["modulo"]);

                // Comprobamos que los campos sean los que hemos declarados
                foreach($this->dataRequest as $nombreParam => $valor)
                {
                       if (!in_array($nombreParam, $validParams))
                          throw new paramNotValid("Parameter $nombreParam not valid for this service");
                }
         }

         /**
          * Metode que agafa les dades d'entrada del webservice depenent del metode HTTP.
          * Aquest metode no es pot sobrecarregar quan es faci una classe derivada de webServiceBase
          * 
          */
         
         private function getData()
         {
                // Dependiendo del tipo de metodo usado cogemos unas variables u otras
                $this->dataRawRequest = file_get_contents("php://input");
                
                switch($this->method) 
                {
                        case "GET": $this->dataRequest = $_GET;
                                    break;

                       case "POST": if (count($_POST) > 0)
                                        $this->dataRequest = $_POST;
                                    else parse_str($this->dataRawRequest, $this->dataRequest);
                                    break;

                    case 'DELETE':
                    case 'PUT': parse_str($_SERVER["QUERY_STRING"], $this->dataRequest);
                                    break;

                }
         }
         
         /**
          * Metode abstracte per a comprovar els paràmetres especifics del webservice, aquest mètode
          * no s'implementa aqui, si no a la classe filla, que és la qual sap quins paràmetres comprovar.
          */
         
         abstract public function checkParams();
         
         /**
          * Metode que executa el fluxe del webservice quan es fa una petició a ell
          */
         
         final public function run()
         {
                $mapMethods = array("GET"    => "getMethod",
                                    "POST"   => "postMethod", 
                                    "PUT"    => "putMethod",
                                    "DELETE" => "deleteMethod");

                $tIni = microtime($get_as_float = true);
                
                $this->result = $this->$mapMethods[$this->method]();
                
                $this->runTime = microtime($get_as_float = true) - $tIni;
         }
         
         final public function getExecutionTime()
         {
                      return ($this->runTime);    
         }
         
         /**
          * Permet reconfigurar desde el propi webservice el tipus de contigut que retornarà
          * 
          * @param int $content Tipus de content que retorna aquest webservice, per defecte JSON.
          */
         
         protected function setContentType($content)
         {
                   $this->typeOut = $content;
         }
         
         /**
          * Ens retorna el mimetype de la resposta
          * 
          * @return string Cadena que s'especifica al header de la resposta, estil: Content-Type: application/json.
          */

         public function contentType()
         {
                if ($this->typeOut == self::JSON_TYPE)
                    return "application/json";

                if ($this->typeOut == self::PDF_TYPE)
                    return "application/pdf";
                
                if ($this->typeOut == self::XML_TYPE)
                    return "text/xml";
                
                if ($this->typeOut == self::HTML_TYPE)
                    return "text/html";
                                    
                return "text/plain";
         }

         public function returnCode()
         {
                return (dataResponse::getResponseHTTPCode($this->result));
         }
         
         /**
          * Ens torna el resultat del servei amb el format que nosaltres necesitam
          * 
          * @return string Contingut de la resposta del webservice
          */
         
         public function getDataResult()
         {
                if ($this->typeOut == self::JSON_TYPE)
                {
                    $retval = json_encode($this->result->data);
                    $errNo = json_last_error();
                    
                    if ($errNo != JSON_ERROR_NONE)
                        throw new JSONEncodingError($errNo);
                    
                    return $retval;
                }
                
                if ($this->typeOut == self::XML_TYPE)
                {
                    $xml = new SimpleXMLElement('<results/>');
                    array_walk_recursive($this->result->data, function ($value, $key) use ($xml) {
                                   if($value)
                                      $xml->addChild($key, utf8_encode($value));
                            });
                    return $xml->asXML();
                }
                
                return $this->result->data;
         }
         
         /**
          * Treu per sortida estandard amb les capçaleres HTTP corresponents el resultat
          * generat.
          */
         
         public function writeResult()
         {
                $dataResult = $this->getDataResult();
                if ($dataResult)
                    header('HTTP/1.1 '.$this->returnCode());
                else
                    header('HTTP/1.1 204 No Conent');
                
                header('Content-Type: '.$this->contentType());
                echo $dataResult;
         }
}