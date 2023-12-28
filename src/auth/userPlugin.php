<?php

namespace gcf\auth;

/**
 * Class userPlugin
 * @property array userInfo
 */
abstract class userPlugin
{
    public array $users = [];
    protected array $userInfo = [];
 
	abstract public function getUser(string $nick);
    abstract public function getUsersList(); 

    public function __get($property)
    {
           $property = strtoupper($property);
           if (isset($this->userInfo[$property]))
               return $this->userInfo[$property];
           return null;
    }

    public function __set($property, $value)
    {
           $property = strtoupper($property);
           $this->userInfo[$property] = $value;
    }

    public function GetUserInfo() : array
    {
        return $this->userInfo;
    }
}