<?php

class baseDades extends dataBaseConn
{
      public function __construct($cadConn, $user, $passwd) 
      {
	         if (!function_exists("sasql_pconnect"))
	        	 throw new errorDatabaseDriver("No hi ha instal.lat el driver de SQLAnywhere!");
				 
             $this->cadConn = "uid=$user;pwd=$passwd;$cadConn";	    
             if (! ($this->connDb = @sasql_pconnect($this->cadConn)) )
	             throw new errorDatabaseConnection("Error al connectar a la base de dades de SQLAnywhere");
		   
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
}

?>
