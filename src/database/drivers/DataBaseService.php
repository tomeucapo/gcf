<?php
namespace gcf\database\drivers;

use stdClass;

abstract class DataBaseService
{
     protected string $host;
     protected string $user, $passwd;
     protected mixed $service;

    /**
     * @var stdClass
     */
     public stdClass $info;

     abstract public function Close() : void;
     abstract public function AddUser(string $userName, string $passwd, string $cn) : void;
     abstract public function DelUser(string $userName) : void;

     public function __construct(string $host, string $user, string $passwd)
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
