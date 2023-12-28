<?php
namespace gcf\database\drivers\sqlanywhere;

use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    /**
     * baseDades constructor.
     * @param string $cadConn
     * @param string $user
     * @param string $passwd
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */
      public function __construct(string $cadConn, string $user, string $passwd)
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

      public function Open()
      {
          // TODO: Implement Open() method.
      }
}
