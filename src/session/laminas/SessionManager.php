<?php


namespace gcf\session\laminas;

use gcf\session\SessionManagerBase;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends SessionManagerBase
{
    /**
     * @var Container
     */
    private Container $sessio;

    /**
     * @var LaminasSessionManager
     */
    private LaminasSessionManager $manager;

    public function __construct($name, $expire=3600)
    {
        $name = str_replace(".", "_", $name);

        parent::__construct($name, $expire);

        $this->sessio = new Container($name);
        $this->sessio->setExpirationSeconds($expire);

        $config = new StandardConfig();
        $config->setOptions([
            'remember_me_seconds' => $expire,
            'name'                => $name,
        ]);

        $this->manager = new LaminasSessionManager($config);
        Container::setDefaultManager($this->manager);
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

    public function Start() : void
    {
           $this->sessio = new Container($this->name);
           $this->sessio->setExpirationSeconds($this->expire);
    }

    public function End() : void
    {
            $this->manager->destroy();
    }

    public function Id() : string
    {
            return $this->manager->getId();
    }

    /**
     * @param $id
     * @return LaminasSessionManager
     */
    public function SetSessionID($id) : LaminasSessionManager
    {
            return $this->manager->setId($id);
    }
}