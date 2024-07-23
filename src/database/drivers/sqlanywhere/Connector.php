<?php
namespace gcf\database\drivers\sqlanywhere;

use gcf\database\ConnectionMode;
use gcf\database\DataBaseConnProps;
use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    /**
     * baseDades constructor.
     * @param DataBaseConnProps $props
     * @param string $role
     * @param ConnectionMode $mode
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */
      public function __construct(DataBaseConnProps $props, string $role, ConnectionMode $mode=ConnectionMode::NORMAL)
      {
             $funcConn = ($mode == ConnectionMode::NORMAL) ? "sasql_connect" : "sasql_pconnect";
	         if (!function_exists($funcConn))
	        	 throw new errorDatabaseDriver("$funcConn not installed in your PHP!");
				 
             $cadConn = "uid=$props->user;pwd=$props->passwd;$props->cadConn";
             if (! ($this->connDb = @$funcConn($cadConn)) )
	             throw new errorDatabaseConnection("Error al connecting to $props->cadConn");
		   
    	    $this->drvId = "SQLANYWHERE";
      }
	  
      public function Close()
      {
	     if ($this->connDb)
	         @sasql_disconnect($this->connDb);
      }
	  
      public function lastError()
      {
	     return(sasql_error($this->connDb));
      }

      public function Open() : void
      {
          // TODO: Implement Open() method.
      }
}
