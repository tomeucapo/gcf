<?php

class errorDatabaseService extends Exception {};
class errorAddUser extends Exception {};
class errroDelUser extends Exception {};

abstract class dataBaseService
{
     protected $host;
     protected $user, $passwd;
     protected $service;

    /**
     * @var stdClass
     */
     public $info;

     abstract public function Close();


    /**
     * @param $userName
     * @param $passwd
     * @param $cn
     * @return mixed
     */
     abstract public function AddUser($userName, $passwd, $cn);

     abstract public function DelUser($userName);
  
	 public function __construct($host, $user, $passwd)
     {
            $this->host = $host;
            $this->user = $user;
            $this->passwd = $passwd;

            $this->info = new stdClass();
            $this->info->version = "0";
            $this->info->platform = "unknown";
     }

     public function __destruct()
     {
            $this->Close();
     }
}
