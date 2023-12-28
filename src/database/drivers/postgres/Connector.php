<?php

namespace gcf\database\drivers\postgres;

use gcf\database\ConnectionMode;
use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    /**
     * @throws errorDatabaseDriver
     */
    public function __construct(string $cadConn, string $user, string $passwd, string $role, ConnectionMode $mode=ConnectionMode::NORMAL)
      {
             $funcConn = ($mode == ConnectionMode::NORMAL) ? "pg_connect" : "pg_pconnect";
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

    public function Open()
    {
        // TODO: Implement Open() method.
    }
}