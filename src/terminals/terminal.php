<?php
  /****************************************************************************************
    clienttime.php
    Classe per gestionar la comunicaciÃ³ amb les terminals que empra la classe clientTime.

    Created......: 19/04/2007
    Last modified: 06/10/2012
    Author.......: Tomeu CapÃ³ CapÃ³
    
  *****************************************************************************************/

namespace gcf\terminals;

use DateTime;
use gcf\terminals\drivers\clientTimeBase;

/**
 * Class terminal
 */
class terminal 
{
    /**
     * Numero maxim de empremtes que poden tenir els terminals
     */
      const MAX_FINGERPRINTS=2;

      var $connected, $num;

    /**
     * Terminal Client Driver
     * @var clientTimeBase
     */
      public $clientTime;

    /**
     * Connection properties
     * @var array
     */
      private $connectionArgs;

    /**
     * terminal constructor.
     * @param clientTimeBase $clientTime Terminal client driver
     * @param string $term_ip Terminal IP
     * @param int $num Terminal number
     * @param int $term_port Terminal port
     * @param string $mode Connection mode (master or slave).
     */
      public function __construct(clientTimeBase $clientTime, $term_ip, $num, $term_port=1000, $mode="master") 
      {
               $this->connected  = false;
               $this->clientTime = $clientTime;
               $this->num        = $num;

               $this->connectionArgs = ["ip"     => $term_ip,
		                                "port"   => $term_port,
                 		                "number" => $num];

               if($this->clientTime->sendCommand("SERVER", "connectnetwork", $this->connectionArgs)>=0)
                  $this->connected = true;               
      }

    /**
     * MÃ©tode que inicialitza la connexiÃ³ al terminal
     */
      public function connect()
      {
               if ($this->connected) return;

               if($this->clientTime->sendCommand("SERVER", "connectnetwork", $this->connectionArgs)>=0) 
                  $this->connected = true; 
      }

    /**
     * Retorna el darrer codi d'error del terminal
     * @return mixed
     */
      public function getErrorCode()
      {
	     return $this->clientTime->getErrorCode();
      }

      public function getLastError() : string
      {
          return $this->clientTime->getLastError();
      }

    /**
     * Retorna el darrer estat de comanda del terminal
     * @return mixed
     */
      public function getStatusCode()
      {
        return $this->clientTime->getStatus();
      }

    /**
     * Mètode per llegir la data/hora del terminal
     * @return string Hora i data separada amb un ";"
     */
      public function getTime()
      {
               $res = '';
               if($this->connected)
               {
                  if($this->clientTime->sendCommand("TARGET", "gettime")>=0) 
                  {
                      $res = $this->clientTime->campsRes['time']."; ".$this->clientTime->campsRes['date'];
                  } 
               }
               return $res;
      }

     /**
      * Mètode per ajustar la data/hora del terminal
      * @param $data
      * @param $hora
      * @return bool
      */
      public function setTime(DateTime $dataHora, string $dateFormat) : bool
      {
               if(!$this->connected)
                   return false;

               return ($this->clientTime->sendCommand("TARGET", "settime",
                       ["time" => $dataHora->format("H:i:s"),
                        "date" => $dataHora->format($dateFormat)])>=0);
      }

    /**
     * Mètode per determinar l'estat del terminal
     * @return array
     */
      public function getStatus() : array
      {
               $res = array();

               if($this->connected)
                  if($this->clientTime->sendCommand("TARGET", "getstatus")>=0) 
                      $res = $this->clientTime->campsRes;

               return $res;
      }

    /**
     * Mètode per determinar la configuració del terminal
     * @throws getConfigException
     */
      public function getConfig() : array
      {
               $res = [];
               if(!$this->connected)
                   return $res;

               if($this->clientTime->sendCommand("TARGET", "getconfig")>=0) {
                   $res = $this->clientTime->campsRes;
               } else throw new getConfigException("Error getting terminal configuration: (".
                   $this->clientTime->getErrorCode().") ".$this->clientTime->getStatus());

               return $res;
      }

      // Mètode per configurar el terminal

      public function setConfig(array $l_config) : bool
      {
               if($this->connected)
                  return($this->clientTime->sendCommand("TARGET", "config", $l_config)>=0);
               return false;
      }

      // Mètode que ens llegeix la informació general del terminal

      public function systemInfo() : array
      {
               $res = array();

               if($this->connected)
               {
                  if($this->clientTime->sendCommand("TARGET", "systeminfo")>=0) 
                  {
                      $res = $this->clientTime->campsRes;
                  } 
               }

               return $res;
      }

    /**
     * Mètode que carrega una llista d'incidències a la terminal
     * @param array $l_incidencies
     * @return bool
     */
      public function sendIncidences(array $l_incidencies) : bool
      {
               if(!$this->connected)
                    return false;

               $max = count($l_incidencies);
               $darrera = "false";

               $this->clientTime->clearCommandsBlock(); $i=0;

               foreach($l_incidencies as $codi => $descripcio)
               {
                      $i++; if ($i == $max) $darrera = "true";
                      $this->clientTime->addCommandToBlock("sendincidences", ["last" => $darrera,
                                                                                   "code" => $codi,
                                                                                   "text" => $descripcio]);
               }

               if($this->clientTime->sendCommandsBlock()<0)
                  return false;
               return true;
      }

      // Mètode que borra totes les incidències de la terminal

      public function deleteIncidences() : bool
      {
               if($this->connected)
                  return($this->clientTime->sendCommand("TARGET", "deleteincidences")>=0);
               return false;
      }

      // Mètode per descarregar els marcatges del terminal

      public function getTransactions($mode="all")
      {
               if($this->connected)
               {
                    if($mode=="all") $s_mode=$mode; 
                    else             $s_mode="new";

                    if($this->clientTime->sendCommand("TARGET", "get".$s_mode."transactions")>=0)
                       return($this->clientTime->blockResponse());
                    return false;
               }
               return false;
      }

      // Mètode per borrar els marcatges del terminal

      public function deleteTransactions()
      {
               if(!$this->connected)
                  return false;
               return($this->clientTime->sendCommand("TARGET", "deletetransactions")>=0);
      }

      // Mètode que permet activar/desactivar l'enmagatzemament dels marcatges dins el terminal

      public function setTransStorage($active)
      {
               if(!$this->connected)
                  return false;

               return($this->clientTime->sendCommand("TARGET", "settransactionstorage", 
							array("enabled" => $active ? "true" : "false"))>=0);
      }

      ///////////////////////////////////////////////////////////////////////////////////////////////////
      // Metodes per a la gestió de les empremptes

    /**
     * Obte un conjunt d'empremtes d'un terminal
     * @param $ids
     * @return bool|mixed
     */
      public function bioGetFingerPrints($ids)
      {
               if(!$this->connected)
				  return false;
               
               if(is_array($ids)) 
			   {
                  $max = count($ids); $i=0;
                  $darrera = "false";

                  $this->clientTime->clearCommandsBlock(); $i=0;

                  foreach($ids as $id) 
                  { 
                          $i++; if ($i == $max) $darrera = "true";
                          $this->clientTime->addCommandToBlock("biogetfingerprints", array("last" => $darrera,
                                                                                           "id" => $id));
                  } 

                  if($this->clientTime->sendCommandsBlock()<0)
                     $retval = false;

               } else
                 if (is_string($ids)) 
                     $this->clientTime->sendCommand("TARGET", "biogetfingerprints", array("last" => "true","id" => $ids));

               return $this->clientTime->blockResponse();
      }

    /**
     * Mètode que carrega una llista d'usuaris a la terminal
     * @param array $l_fingers
     * @return bool
     */
      public function bioSendFingerPrints(array $l_fingers)
      {
               $retval = false;$envia = true;
               if(!$this->connected)
                  return false;

               $retval = true;$max = count($l_fingers); 
               $this->clientTime->clearCommandsBlock();

	       	   $i = 0;
               foreach($l_fingers as $codi => $data) 
               {
                      $i++; 
                      $num_fingers = (int) $data[0]; 

                      if($num_fingers <= self::MAX_FINGERPRINTS)
                         $this->clientTime->addCommandToBlock("biosendfingerprints", 
	             			array("last"          => ($i == $max) ? "true" : "false",
                                              "id"            => $codi,
                                              "nfingerprints" => $data[0],
                                              "data"          => $data[1]));
                      else
                         $envia = false;
               } 
 
               if($envia)
                  if($this->clientTime->sendCommandsBlock()<0) 
                     $retval = false;

               return $retval;
      }

    /**
     *  Borra un conjunt d'empremtes d'un terminal
     * @param $ids
     * @return bool
     */
      public function bioDeleteFingerPrints($ids)
      {
               if(!$this->connected)
				  return false;

               if(is_array($ids)) 
               {
                  $this->clientTime->clearCommandsBlock();

                  $max = count($ids);$i=0; 
                  foreach($ids as $id) 
                  { 
                          $i++;
                          $this->clientTime->addCommandToBlock("biodeleteusers", array("last" => ($i == $max) ? "true" : "false",
                                                                                       "id"   => $id));
                  } 

                  return ( $this->clientTime->sendCommandsBlock() >= 0 ); 

               } else
                 if (is_string($ids))
                     return ( $this->clientTime->sendCommand("TARGET", "biodeleteusers", array("last" => "true","id" => $ids)) >= 0 );

               return true;
      }

      /**
       * Borra totes les empremtes d'un terminal
       * @return boolean
       */
      public function bioDeleteAllUsers()
      {
               if($this->connected) 
                  return($this->clientTime->sendCommand("TARGET", "biodeleteallusers")>=0);
               return false;
      }

      ///////////////////////////////////////////////////////////////////////////////////////////////////
      // Metodes per a la gestió de les comptes d'usuari 

      // Mètode que carrega una llista d'usuaris a la terminal

      public function createUsers($l_users)
      {
               $retval = false;
               if($this->connected)
               {
                  $retval = true;$max = count($l_users); $i=0;
                  $darrera = "false";

                  $this->clientTime->clearCommandsBlock(); $i=0;

                  foreach($l_users as $codi => $nom) 
                  {
                         $i++; if ($i == $max) $darrera = "true";
                         $this->clientTime->addCommandToBlock("createusers", array("last"          => $darrera,
                                                                                   "name"          => $nom,
                                                                                   "personal_code" => $codi));
                  } 

                  if($this->clientTime->sendCommandsBlock()<0) 
                     $retval = false;
               }

               return $retval;
      }
      
      // Metode per obtenir la llista d'usuaris d'un terminal

      public function getUsers($id_users)
      {
               if($this->connected)
               {
                  if(is_array($id_users)) {
                     $max = count($id_users); $i=0;
                     $darrera = "false";

                     $this->clientTime->clearCommandsBlock(); $i=0;

                     foreach($id_users as $id) 
                     { 
                             $i++; if ($i == $max) $darrera = "true";
                             $this->clientTime->addCommandToBlock("getusers", array("last"          => $darrera,
                                                                                    "personal_code" => $id));
                     } 

                     if($this->clientTime->sendCommandsBlock()<0) 
                        $retval = false;

                  } else
                    if (is_string($id_users)) 
                        $this->clientTime->sendCommand("TARGET", "getusers", array("last" => "true","personal_code" => $id_users));

                  $retval = $this->clientTime->blockResponse();
               } else 
                 $retval = false;

               return $retval;
      }

      // Mètode que borra una llista d'usuaris a la terminal

      public function deleteUsers($l_users)
      {
               $retval = false;
               if($this->connected)
               {
                  $retval = true;$max = count($l_users); $i=0;
                  $darrera = "false";

                  $this->clientTime->clearCommandsBlock(); $i=0;

                  foreach($l_users as $codi) 
                  {
                         $i++; if ($i == $max) $darrera = "true";
                         $this->clientTime->addCommandToBlock("deleteusers", array("last"          => $darrera,
                                                                                   "personal_code" => $codi));
                  } 

                  if($this->clientTime->sendCommandsBlock()<0) 
                     $retval = false;
               }

               return $retval;
      }

      // Mètode per borrar tots els usuaris d'un terminal

      public function deleteAllUsers()
      {
               if(!$this->connected) 
                  return false;
               return($this->clientTime->sendCommand("TARGET", "deleteallusers")>=0);
      }

      // Mètode per a configurar els permisos d'un usuari creat amb createUser

      public function setUsersFunctionsAndPermissions($l_functions)
      {
               if(!$this->connected)
                  return false;

               $max = count($l_functions);
               $darrera = "false";

               $this->clientTime->clearCommandsBlock(); $i=0;

               foreach($l_functions as $idPersona => $permissions)
               {
                      $i++; if ($i == $max) $darrera = "true";

                      $lConfig1 =  ["last" => $darrera, "personal_code" => $idPersona];
                      $lConfigUser = array_merge($lConfig1, $permissions);

                      $this->clientTime->addCommandToBlock("setusersfunctionsandpermissions", $lConfigUser);
               }

               if($this->clientTime->sendCommandsBlock()<0)
                   return false;

               return true;
      }

      ///////////////////////////////////////////////////////////////////////////////////////////////////
      // Metodes pels missatges de pantalla

      // Mètode que permet activar/desactivar la visualitzacio del nom del usuari per pantalla

      public function setUserNameDisplaying($active)
      {
               if(!$this->connected)
                  return false;
               return($this->clientTime->sendCommand("TARGET", "setusernamedisplaying", array("enabled" => $active ? "true" : "false"))>=0);
      }
      
      // Mostra un missatge per la pantalla del terminal

      public function displayMessageNow($msg)
      {
               if(!$this->connected) 
                  return false;

               return($this->clientTime->sendCommand("TARGET", "displaymessage", array("message" => $msg))>=0);
      }

      // Mostra un missatge per la pantalla del terminal

      public function programCommonMessage($msg)
      {
               if(!$this->connected) 
                  return false;

               return($this->clientTime->sendCommand("TARGET", "programcommonmessage", array("message" => $msg))>=0);
      }
      
      // Ens borra el missatge actual que surt al terminal

      public function clearMessage()
      {
               if(!$this->connected)
                  return false;

               return($this->clientTime->sendCommand("TARGET", "clearmessage")>=0);
      }

      ///////////////////////////////////////////////////////////////////////////////////////////////////
      // Metodes pels missatges de veu

      // Activa els missatges de veu

      public function setVoiceMessagesFunction($active)
      {
               if(!$this->connected)
                  return false;

               return($this->clientTime->sendCommand("TARGET", "setvoicemessagesfunction", array("enabled" => $active ? "true" : "false"))>=0);
      }

      // Envia missatges de veu a la terminal

      public function sendVoiceMessages($voxMessages)
      {
               if(empty($voxMessages))
                   return false;

               if(!$this->connected)
                   return false;

               $max = count($voxMessages);
               $darrera = "false";

               $this->clientTime->clearCommandsBlock(); $i=0;
               foreach($voxMessages as $dataHex)
               {
                      $i++; if ($i == $max) $darrera = "true";
                      $this->clientTime->addCommandToBlock("sendvoicemessages", ["last"    => $darrera,
                                                                                      "data"    => $dataHex]);
               }

               if($this->clientTime->sendCommandsBlock()<0)
                  return false;

               return true;
      }


      ///////////////////////////////////////////////////////////////////////////////////////////////////
      // Metodes per configurar els contadors dels usuaris (Vacances i d'altres)

      public function setUsersHollidays($l_hollidays)
      {
               if(!$this->connected)
                 return false;

               $max = count($l_hollidays);
               $darrera = "false";

               $this->clientTime->clearCommandsBlock();

               $i=0;
               foreach($l_hollidays as $codi => $contador)
               {
                      $i++; if ($i == $max) $darrera = "true";

                      $this->clientTime->addCommandToBlock("setusershollidays", ["last"          => $darrera,
                                                                                      "personal_code" => $codi,
                                                                                      "days"          => $contador[0],
                                                                                      "hours"         => $contador[1]]);

               }
 
               if($this->clientTime->sendCommandsBlock()<0) {
                   return false;
               }

               return true;
      }

    /**
     * @param $active
     * @return bool
     */
      public function setHollidaysConsulting($active) : bool
      {
               if(!$this->connected)
                  return false;

               return($this->clientTime->sendCommand("TARGET", "sethollidaysconsulting",
                                                    ["enabled" => $active ? "true" : "false"])>=0);
      }

      /**
       * Metode per desconnectar-se del terminal
       * @return boolean
       */
      public function disconnect() : bool
      { 
               if($this->clientTime->sendCommand("SERVER", "disconnect")>=0)
               {
                   $this->connected = false;
                   return true;
               }
               return false;
      }
}
