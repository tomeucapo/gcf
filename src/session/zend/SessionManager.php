<?php

namespace gcf\session\zend;

include_once "Zend/Session.php";

use gcf\session\SessionException;
use gcf\session\SessionManagerBase;
use Zend_Session;
use Zend_Session_Exception;
use Zend_Session_Namespace;

class SessionManager extends SessionManagerBase
{
    /**
     * @var Zend_Session_Namespace
     */
    private Zend_Session_Namespace $sessio;

    /**
     * SessionManager constructor.
     * @param string $name
     * @param int $expire
     * @throws SessionException
     */
    public function __construct(string $name, int $expire=3600)
    {
        parent::__construct($name, $expire);
        try {
            $this->sessio = new Zend_Session_Namespace($name);
        } catch (Zend_Session_Exception $e) {
            throw new SessionException("Error iniciant la sessio: ".$e->getMessage());
        }
    }

    public function __set($name, $value)
    {
        $this->sessio->$name = $value;
    }

    public function __get($name)
    {
        return $this->sessio->$name;
    }

    public function __isset($name)
    {
        return (isset($this->sessio->$name));
    }

    /**
     * @throws Zend_Session_Exception
     */
    public function Start() : void
    {
        $this->sessio->setExpirationSeconds($this->expire);
    }

    /**
     *
     * @throws SessionException
     */
    public function End()
    {
        try {
            if ( $this->sessio->isLocked())
                $this->sessio->unLock();

            Zend_Session::namespaceUnset($this->name);
        } catch(Zend_Session_Exception $e) {
            throw new SessionException($e->getMessage());
        }
    }

    public function Id()
    {

    }

    /**
     * @param $id
     * @throws SessionException
     */
    public function SetSessionID($id) : void
    {
        try {
            Zend_Session::setId($id);
        } catch (Zend_Session_Exception $e) {
            throw new SessionException($e->getMessage());
        }
    }
}