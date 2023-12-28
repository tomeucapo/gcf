<?php

namespace gcf\session\php;

use gcf\session\SessionException;
use gcf\session\SessionManagerBase;

class SessionManager extends SessionManagerBase
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

    public function Id()
    {
        return(@session_id());
    }

    public function SetSessionID($id)
    {
        @session_id($id);
    }

    /**
     * Start new session
     * @throws SessionException
     */
    public function Start()
    {
        @session_name($this->name);
        @session_cache_expire($this->expire);
        if (!session_start())
            throw new SessionException("Error starting new session {$this->name}");
    }

    /**
     * End session
     * @throws SessionException
     */
    public function End()
    {
        @session_name($this->name);
        @session_start();
        if (!session_destroy())
            throw new SessionException("Error ending new session {$this->name}");
    }
}