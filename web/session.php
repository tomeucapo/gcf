<?php

function parseRequestHeaders() 
{
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
}
    
abstract class SessionStorage
{
      protected $name, $expire;
      protected $data;

      public function __construct($name, $expire)
      {
             $this->name = $name;
             $this->expire = $expire;
      }    
      
      abstract public function start();
      abstract public function end();
}

class SessionPHP extends SessionStorage
{
      public function __construct($name, $expire="5")
      {
             parent::__construct($name, $expire);
                          
             @session_name($name);
             @session_cache_expire($expire);
      }
      
      public function __set($name, $value) 
      {
             $_SESSION[$name] = $value;
      }

      public function __get($name) 
      {
             return $_SESSION[$name];
      }
      
      public function __isset($name) 
      {
             return (isset($_SESSION[$name]));
      }

      public function id()
      {
             return(@session_id());
      }
      
      public function setSession($id)
      {
             @session_id($id);    
      }
             
      public function start()
      {
             @session_name($this->name);
             @session_cache_expire($this->expire);
             @session_start();        
      }
      
      public function end()
      {
             @session_name($this->name);
             @session_start();
             @session_destroy();           
      }
}