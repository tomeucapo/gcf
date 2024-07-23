<?php

namespace gcf\database;

use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\DataBaseService;

class DatabaseConnector
{
    /**
     * Database connection object
     * @var DataBaseConn
     */
      public DataBaseConn $dataBase;

    /**
     * Database driver name
     */
      public readonly string $drv;

    /**
     * Database connection properties
     * @var DataBaseConnProps
     */
      private DataBaseConnProps $properties;

      private string $className;

      private ConnectionMode $mode;

      private string $myRole = "";

      private ?DataBaseService $service = null;


    /**
     * Database connection constructor. Establish connection when you instantiate the class
     * @param string $cadConn
     * @param string $user
     * @param string $passwd
     * @param ConnectionMode $mode
     * @param string $drv
     * @param ?string $my_role
     * @throws errorDriverDB
     */

      public function __construct(string $cadConn, string $user, string $passwd, ConnectionMode $mode=ConnectionMode::NORMAL, string $drv="firebird", ?string $my_role="")
      {
			 if (empty($drv))
				throw new errorDriverDB("Driver not defined!");

             $this->drv = strtolower($drv);
             $this->className = "gcf\\database\\drivers\\$this->drv\\Connector";
             if(!class_exists($this->className))
				throw new errorDriverDB("Class $this->className not exists in your PHP installation");

             $this->properties = new DataBaseConnProps($user, $passwd, $cadConn);

             if (!empty($my_role))
			    $this->myRole = $my_role;

			 $this->mode = $mode;

			 $this->connecta();
      }

	  private function connecta() : void
	  {
             $this->dataBase = new $this->className(
                 $this->properties,
                 $this->myRole,
                 $this->mode
             );
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
				throw new errorDriverDB("Class $className not exists in your PHP installation");

             $this->service = new $className($this->properties->cadConn, $usrAdmin, $passwdAdmin);
             return $this->service;
      }

      public function endoll_db() : dataBaseConn
      {
             return $this->dataBase;
      }

      public function desconnecta() : void
      {
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
