<?php

/**
 * Classe que defineix una responsta HTTP del webservice, tenim per una banda el codi de resposta HTTP
 * i per l'altre les dades.
 */

class dataResponse 
{
      /**
       * Codis de resposta HTTP 
       */
      const R_OK = 200;                
      const R_CREATED = 201;
      const R_ACCEPTED = 202;
      const R_DELETED = 204;
      const R_NOT_MODIFIED = 304;
      const R_FOUND = 302;
      const R_NOT_FOUND = 404;
      const R_UNAUTHORIZED = 401;
      const R_DUPLICATE_ENTRY = 409;
      const R_ERROR = 500;
      
      public $data, $code;
      
      public function __construct($data, $code=self::R_OK) 
      {
             $this->data = $data;
             $this->code = $code;
      }     
      
      /**
       * Métode estàtic per determinar quin missatge hem de tornar a la capçalera
       * 
       * @param dataResponse $dr 
       * @return string Missatge corresponent al codi de resposta
       */
      
      public static function getResponseHTTPCode(dataResponse $dr)
      {
             $msgResponse = array(self::R_OK           => "OK",
                                  self::R_CREATED      => "Created", 
                                  self::R_ACCEPTED     => "Accepted",
                                  self::R_FOUND        => "Found",
                                  self::R_NOT_FOUND    => "Not Found",
                                  self::R_NOT_MODIFIED => "Not modified",
                                  self::R_ERROR        => "Internal process error",
                                  self::R_UNAUTHORIZED => "Unauthorized");
                                    
             return ($dr->code." ".$msgResponse[$dr->code]);
      }
}

?>
