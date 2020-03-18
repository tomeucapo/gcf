<?php

class baseDadesPostgres extends dataBaseConn
{
      public function __construct($cadConn, $user, $passwd, $role, $mode="N") 
      {
             $funcConn = ($mode == "N") ? "pg_connect" : "pg_pconnect";
             if (!function_exists($funcConn))
                throw new errorDatabaseDriver("No hi ha instal.lat el driver de Postgres!");

             $this->cadConn = "$cadConn user=$user password=$passwd";
                
             if (!($this->connDb = @$funcConn($this->cadConn)))
                throw new errorDatabaseConnection("Error de connexio a Postgres: ".$cadConn);
      }
	  
      public function Close()
      {
             if ($this->connDb)
    	        @pg_close($this->connDb);
      }   
      
      public function lastError()
      {
	 	    return pg_errormessage($this->connDb);
      }
      
      public function __destruct()
      {
	    $this->Close();
      }
}