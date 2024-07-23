<?php
namespace gcf\database\drivers\oracle;

use gcf\database\ConnectionMode;
use gcf\database\DataBaseConnProps;
use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    /**
     * baseDadesOracle constructor.
     * @param DataBaseConnProps $props Connection properties
     * @param string $role Not used
     * @param ConnectionMode $mode Connection mode: Normal or Persistent
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */
      public function __construct(DataBaseConnProps $props, string $role, ConnectionMode $mode = ConnectionMode::NORMAL)
      {
             $funcConn = ($mode ==  ConnectionMode::NORMAL) ? "ocilogon" : "ociplogon";
	         if (!function_exists($funcConn))
		        throw new errorDatabaseDriver("$funcConn not installed in your PHP!");
				 
	         if (!($this->connDb = @$funcConn($props->user, $props->passwd, $props->cadConn)))
                throw new errorDatabaseConnection("Error connecting to: ".$props->cadConn);
				 
	         $this->cadConn = $props->cadConn;
      }
	  
      public function Close()
      {
	     if ($this->connDb)
	   	     @ocilogoff($this->connDb);
      }
	  
      public function lastError()
      {
	     $err = oci_error($this->connDb);
	     if ($err)
		     return($err["message"]);
	     return "";
      }
	  
      public function __destruct()
      {
	     $this->Close();
      }

    public function Open(): void
    {
        // TODO: Implement Open() method.
    }
}