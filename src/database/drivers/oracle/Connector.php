<?php
namespace gcf\database\drivers\oracle;

use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    /**
     * baseDadesOracle constructor.
     * @param $cadConn
     * @param $user
     * @param $passwd
     * @param $role
     * @param string $mode
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */
      public function __construct($cadConn, $user, $passwd, $role, $mode="N") 
      {
             $funcConn = ($mode == "N") ? "ocilogon" : "ociplogon";
	         if (!function_exists($funcConn))
		        throw new errorDatabaseDriver("No hi ha instal.lat el driver de Oracle!");
				 
	         if (!($this->connDb = @$funcConn($user, $passwd, $cadConn)))
                throw new errorDatabaseConnection("Error al connectar a: ".$cadConn);          
				 
	         $this->cadConn = $cadConn;
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

    public function Open()
    {
        // TODO: Implement Open() method.
    }
}