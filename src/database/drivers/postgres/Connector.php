<?php

namespace gcf\database\drivers\postgres;

use gcf\database\ConnectionMode;
use gcf\database\DataBaseConnProps;
use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    /**
     * @param DataBaseConnProps $props
     * @param string $role
     * @param ConnectionMode $mode
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */
    public function __construct(DataBaseConnProps $props, string $role, ConnectionMode $mode=ConnectionMode::NORMAL)
      {
             $funcConn = ($mode == ConnectionMode::NORMAL) ? "pg_connect" : "pg_pconnect";
             if (!function_exists($funcConn))
                throw new errorDatabaseDriver("$funcConn not installed in your PHP!");

             $cadConn = "$props->cadConn user=$props->user password=$props->passwd";
                
             if (!($this->connDb = @$funcConn($cadConn)))
                throw new errorDatabaseConnection("Error connecting to PostgresSQL");
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

    public function Open() : void
    {
        // TODO: Implement Open() method.
    }
}