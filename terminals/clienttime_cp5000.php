<?php
/****************************************************************************************
    clienttime.php
    Classe per gestionar la connexió via socket TCP/IP al servidor ServerTime.

    Created......: 18/04/2007
    Last modified: 06/10/2012
    Author.......: Tomeu Capó Capó 2012 (C)

    Alcúdia Marítima S.A. 2002/12 (C)
  *****************************************************************************************/

include_once "clientTimeBase.php";

class clientTimeCP5000 extends clientTimeBase  
{
    const DEBUG = true;
    private $srv_port;
    private $L_STATUS_ERR, $L_ERRCODES;

    /**
     * clientTimeCP5000 constructor.
     * @param $srv_ip string Adreça IP del servidor ServerTime
     * @param int $srv_port int Port TCP del servidor
     */
    public function __construct($srv_ip, $srv_port = 1000)
    {
        $this->sk = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($this->sk,
            SOL_SOCKET,
            SO_RCVTIMEO,
            array('sec' => 300,
                'usec' => 0));

        $this->srv_ip = $srv_ip;
        $this->srv_port = $srv_port;

        $this->buff = '';
        $this->errcode = '';
        $this->status = '';
        $this->blockSnd = '';
        $this->listResponses = array();

        $this->L_STATUS_ERR = array("BAD ARGUMENTS",
            "ERROR",
            "UNKNOWN COMMAND",
            "INVALID XML SYNTAX");

        $this->L_ERRCODES = array("BAD_ARGUMENTS",
            "NOT_CONNECTED",
            "SERIAL_READ_ERROR",
            "TCP_READ_ERROR",
            "NO_RESPONSE",
            "TCP_OPEN_ERROR");


    }

    /**
     * Realitzam la connexió TCP/IP amb el servidor
     * @return mixed|void
     * @throws socketBlock
     * @throws socketTimeout
     */
    public function open()
    {
          $timeout = 15;

          if(!socket_set_nonblock($this->sk))
            throw new socketBlock("No puc possar el socket com a no bloquejant ...\n");

          $time = time();
          while (!@socket_connect($this->sk, $this->srv_ip, $this->srv_port))
          {
                 $err = socket_last_error($this->sk);
                 if ($err == 115 || $err == 114)
                 {
                     if ((time() - $time) >= $timeout)
                     {
                         socket_close($this->sk);
                         throw new socketTimeout("Connection timed out!\n");
                     }
                     sleep(1);
                     continue;
                 }
                 //echo socket_strerror($err)." ".$this->srv_ip.":".this->srv_port."\n";
          }

          if(!socket_set_block($this->sk))
             throw new socketBlock("No puc canviar el socket a mode bloquejant ...\n");
    }

    /**
     * Envia una trama XML al servidor, i agafam la resposta
     * @param $xml string Petició XML a enviar
     * @return int Estat
     */
    private function sendXML($xml)
    {
            if(self::DEBUG)
               echo "[ TX ] $xml\n";

            if(socket_send($this->sk, $xml, strlen($xml), 0)>0)
            {
                 $tr=""; $this->buff = "";$retval = 0;

                 while($tr != ">")
                 {
                       if(socket_recv($this->sk, $tr, 1, 0)>0)
                          $this->buff .= $tr;
                       else {
                          $retval = -1;break;
                       }
                 }

               sleep(1);
            } else
              $retval = -2;

            return $retval;
    }

    /**
     * Funció que monta un string var=value en base a un array d'entrada
     * @param $l_args
     * @return string
     */
      private function mountArgs($l_args)
      {
               $retval = "";
               if(is_array($l_args)) 
               { 
                   foreach($l_args as $name => $value)
                   {
                           $retval.= "$name=\"$value\" ";
                   }
               }
               return $retval;
      }

    /**
     * Envia una trama XML al servidor, i agafam la resposta
     * @param string $type
     * @param string $cmd
     * @param string $l_args
     * @return int|mixed
     * @throws Exception
     */
      public function sendCommand($type, $cmd, $l_args="")
      {
               if(strlen($cmd)==0)
                   return -1;

               $s_args = $this->mountArgs($l_args);
               $this->listResponses = array();

               if($type=="SERVER")
                  $cmdXML = "<$cmd $s_args />";
               else
                  $cmdXML = "<command op=\"$cmd\" $s_args/>";
      
               $this->buff = '';

               $res = $this->sendXML($cmdXML."\r");

               if(($res==0) && (strlen($this->buff)>0)) {
                  if ($this->processResponse()<0)
                     return -3;
               } else 
                  return $res;

               return $res;
      }

      /**********************************************************************
         Mètodes per enviar blocs de comandes al servidor
       **********************************************************************/

    /**
     * @return mixed|void
     */
      public function clearCommandsBlock()
      {
               $this->blockSnd = '';
      }

    /**
     * @param string $cmd
     * @param string $l_args
     * @return mixed|void
     */
      public function addCommandToBlock($cmd, $l_args="")
      {
               $s_args = $this->mountArgs($l_args);
               $newCmd = "<command op=\"$cmd\" $s_args/>";

               if($this->blockSnd)
                  $this->blockSnd .= $newCmd;
               else
                  $this->blockSnd = $newCmd;
      }

    /**
     * @return int|mixed
     * @throws Exception
     */
      public function sendCommandsBlock() 
      {
               if(strlen($this->blockSnd)==0) {
                   return -1;
               }

               $this->buff = '';

               $res = $this->sendXML($this->blockSnd."\r");

               if(($res==0) && (strlen($this->buff)>0)) 
               {
                  if ($this->processResponse()<0)
                     return -3;
               } else {
                   return $res;
               }

               return 0;
      }


    /**
     * Mètodes per tractar els REPLYs del terminal
     *
     * @param $attributes
     */
      
      private function extractFields($attributes)
      {
               $l_fields = array();

               foreach($attributes as $attr) 
                      $l_fields[$attr->name] = $attr->value;

               $this->campsRes = $l_fields;
      }


    /**
     * @return int
     * @throws Exception
     */
    private function processResponse()
      {
               $retval = 0; $final = false;
               while(!$final)
               {
                     if(trim($this->buff)=='')
                     {
                         $retval = -3;
                         break;
                     }
                     
                     if(self::DEBUG)
                     {
                         $buffPrint = trim(str_replace("\r", chr(0), $this->buff));
                         echo "[ RX ".count($this->listResponses)." ] ".$buffPrint."\n";
                     }

                     if (!($domResponse = DOMDocument::loadXML($this->buff)))
                         throw new Exception("Error resposta XML invalida");

                     $root = $domResponse->documentElement;
                     if ($root->tagname !== "reply")
                     {
                         $retval = -2; 
                         break;
                     }
                     
                     $this->extractFields($root->attributes);

                     // Comprova que no hagi tornat cap error conegut

                     if (in_array($this->campsRes['status'], $this->L_STATUS_ERR))
                     {
                         $retval = -1; 
                         $final = true;
                     } else 
                        if (in_array($this->campsRes['errcode'], $this->L_ERRCODES)) 
                        {
                            $retval = -1; 
                            $final = true;
                        }
                        
                     $this->status  = $this->campsRes['status'];
                     $this->errcode = $this->campsRes['errcode'];

                     // Determinar si la resposta és simple o bé composta

                     if (!array_key_exists("last", $this->campsRes)) 
                        break;
                     
                     array_push($this->listResponses, $this->campsRes);
  
                     if($this->campsRes['last']=='true')
                        break;
                         
                     $tr=""; $lastBlock = "";
                     while($tr != ">")
                     {
                           $nr = socket_recv($this->sk, $tr, 1, 0);
                           $lastBlock .= $tr;
                     }

                     $this->buff = $lastBlock; 
               }
                  
               return $retval;
      }

      public function close()
      {
               socket_close($this->sk);
      }
}
