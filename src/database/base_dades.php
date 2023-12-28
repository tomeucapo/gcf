<?php

namespace gcf\database;

use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\DataBaseService;
use gcf\database\drivers\errorDatabaseAutentication;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\errorDriverDB;

class base_dades
{
    /**
     * Database connection object
     * @var DataBaseConn
     */
      public DataBaseConn $dataBase;

    /**
     * Database driver name
     * @var string
     */
      public string $drv;

      private $cadConn, $user, $passwd, $className, $mode;

      private string $myRole = "";

      private ?DataBaseService $service = null;

      public $autoFlushCache;

    /**
     * base_dades constructor.
     * @param string $cadConn
     * @param string $user
     * @param string $passwd
     * @param ConnectionMode $mode
     * @param string $drv
     * @param ?string $my_role
     * @throws errorDriverDB
     * @throws errorDatabaseConnection
     * @throws errorDatabaseAutentication
     */

    public function __construct(string $cadConn, string $user, string $passwd, ConnectionMode $mode=ConnectionMode::NORMAL, string $drv="firebird", ?string $my_role="")
      {
			 if (empty($drv))
				throw new errorDriverDB("No s'ha especificat el driver de base de dades a instanciar!");

             $this->drv = strtolower($drv);
             $this->className = "gcf\\database\\drivers\\$this->drv\\Connector";
             if(!class_exists($this->className))
				throw new errorDriverDB("Hi ha problemes per instanciar la classe del driver de BBDD {$this->className}");

			 $this->cadConn = $cadConn;
			 $this->user = $user;
		     $this->passwd = $passwd;

             if (!empty($my_role))
			    $this->myRole = $my_role;

			 $this->mode = $mode;

             $this->autoFlushCache = false;

			 $this->connecta();
      }

	  private function connecta() : void
	  {
             $this->dataBase = new $this->className($this->cadConn, $this->user, $this->passwd, $this->myRole, $this->mode);
	  }

    /**
     * @param string $usrAdmin
     * @param string $passwdAdmin
     * @return dataBaseService
     * @throws errorDriverDB
     */
      public function getService(string $usrAdmin, string $passwdAdmin) : dataBaseService
      {
             if ($this->service !== null)
                 return $this->service;

             $className = "gcf\\database\\drivers\\$this->drv\\DataBaseService";

			 if(!class_exists($className))
				throw new errorDriverDB("Hi ha problemes per instanciar la classe del driver de servei $className");

             $this->service = new $className($this->cadConn, $usrAdmin, $passwdAdmin);
             return $this->service;
      }

      public function endoll_db() : dataBaseConn
      {
             return $this->dataBase;
      }

      public function desconnecta() : void
      {
             if(!$this->dataBase) return;
             $this->dataBase->Close();
      }

      public function reconnecta() : void
      {
               $this->desconnecta();
               $this->connecta();
      }

      public function __destruct()
      {
             $this->dataBase->Close();
      }
}
