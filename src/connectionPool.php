<?php
/**
 * connectionPool
 *
 * @author tomeu
 */

namespace gcf;

use gcf\database\connectionDb;

class connectionPool
{
      private array $dbConns;
      private string $user;
      private string $dbMain;

      private static ?connectionPool $instance = null;
      
      private function __construct(string $dbMain)
      {
              $this->dbConns = [];
              $this->dbMain = $dbMain;
              $this->user = '';
      }
      
      public static function getInstance(string $dbMain="") : connectionPool
      {
             if (self::$instance === null)
                 self::$instance = new connectionPool($dbMain);

             return self::$instance;
      }

      public function GetMainDBId() : string
      {
          return $this->dbMain;
      }

    /**
     * @param $property
     * @return connectionDb|null
     */
      public function __get($property) 
      {              
              if (isset($this->dbConns[$property]))
                 return $this->dbConns[$property];     
              return null;
      }

      public function __set($property, connectionDb $value)
      {            
             $this->dbConns[$property] = $value;  
      }

      public function getUser() : string
      {
          return $this->user;
      }

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
