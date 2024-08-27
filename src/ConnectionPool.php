<?php
/**
 * connectionPool
 *
 * @author tomeu
 */

namespace gcf;

use gcf\database\ConnectionDb;

class ConnectionPool
{
      private array $dbConns = [];
      private string $user = '';
      private string $dbMain;

      private static ?ConnectionPool $instance = null;
      
      private function __construct(string $dbMain)
      {
              $this->dbMain = $dbMain;
      }
      
      public static function getInstance(string $dbMain="") : ConnectionPool
      {
             if (self::$instance === null)
                 self::$instance = new ConnectionPool($dbMain);

             return self::$instance;
      }

      public function GetMainDBId() : string
      {
          return $this->dbMain;
      }

    /**
     * @param $property
     * @return ConnectionDb|null
     */
      public function __get($property) 
      {              
              if (isset($this->dbConns[$property]))
                 return $this->dbConns[$property];     
              return null;
      }

      public function __set($property, ConnectionDb $value)
      {            
             $this->dbConns[$property] = $value;  
      }

      public function getUser() : string
      {
          return $this->user;
      }

    /**
     * Change all authentication properties of all connections with same user/passwd
     * @param string $user
     * @param string $passwd
     * @param string $role
     * @return void
     */
      public function setAllAuth(string $user, string $passwd, string $role) : void
      {
             $this->user = $user;
             foreach($this->dbConns as $dbConn)
             {
                 if($dbConn->auth == "session")
                 {
                     $dbConn->user = $user;
                     $dbConn->passwd = $passwd;
                     $dbConn->role = $role;
                 }
             }
      }

      public function reconnectAll() : void
      {
          if (empty($this->dbConns)) return;

          foreach($this->dbConns as $dbConn)
          {
              $dbConn->reconnect();
          }
      }

      public function closeAll() : void
      {	
          if (empty($this->dbConns)) return;

          foreach($this->dbConns as $dbConn)
          {
		        $dbConn->close();
		        unset($dbConn);
          }
      }


      public function __destruct()
      {
	        $this->closeAll();
      }
}
