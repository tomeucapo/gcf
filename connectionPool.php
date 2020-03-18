<?php
/**
 * connectionPool
 *
 * @author tomeu
 */

require_once "connectionDb.php";

class connectionPool
{
    /**
     * @var array connectionDb
     */
      private $dbConns;

      private $user;
      private $dbMain;
      private static $instance;
      
      private function __construct($dbMain)
      {
              $this->dbConns = [];
              $this->dbMain = $dbMain;
      }
      
      public static function getInstance($dbMain="")
      {
             if (self::$instance === null)
                 self::$instance = new connectionPool($dbMain);
          
             return self::$instance;
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

      public function getUser()
      {
          return $this->user;
      }

      public function setAllAuth($user, $passwd, $role)
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

      public function closeAll()
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
