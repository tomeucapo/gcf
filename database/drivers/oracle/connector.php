<?php

class baseDadesOracle extends dataBaseConn
{
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
}