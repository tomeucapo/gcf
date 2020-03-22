<?php
/**
 * DatabaseConnector class
 * User: tomeu
 * Date: 4/5/2018
 * Time: 11:38 AM
 */

namespace gcf\database;

class DatabaseConnector
{
    /**
     * Database connection object
     * @var \dataBaseConn
     */
    public $dataBase;

    /**
     * Database driver name
     * @var string
     */
    public $drv;

    private $cadConn, $user, $passwd, $myRole, $className, $service, $mode;
    /**
     * @var bool
     */
    private $autoFlushCache;

    /**
     * DatabaseConnector constructor.
     * @param $cadConn
     * @param $user
     * @param $passwd
     * @param string $mode
     * @param string $drv
     * @param string $my_role
     * @throws errorDriverDB
     */
    public function __construct($cadConn, $user, $passwd, $mode="N", $drv="firebird", $my_role="")
    {
        if (empty($drv))
            throw new errorDriverDB("No s'ha especificat el driver de base de dades a instanciar!");

        $this->drv = strtolower($drv);
        $this->className = "gcf\\database\\drivers\\{$this->drv}\\connector";

        if(!class_exists($this->className))
            throw new errorDriverDB("Hi ha problemes per instanciar la classe del driver de BBDD {$this->className}");

        $this->cadConn = $cadConn;
        $this->user = $user;
        $this->passwd = $passwd;
        $this->myRole = $my_role;
        if ($mode != 'P' && $mode != 'N')
            throw new errorDriverDB("Mode de connexio incorrecte, nomes pot esser P o N");

        $this->mode = $mode;
        $this->autoFlushCache = false;

        $this->connecta();
    }

    private function connecta()
    {
        $className = $this->className;
        $this->dataBase = new $className($this->cadConn, $this->user, $this->passwd, $this->myRole, $this->mode);
    }

    /**
     * @param $usrAdmin
     * @param $passwdAdmin
     * @return mixed
     * @throws errorDriverDB
     */
    public function getService($usrAdmin, $passwdAdmin)
    {
        if ($this->service)
            return $this->service;

        $className = "gcf\\database\\drivers\\{$this->drv}\\service";
        if(!class_exists($className))
            throw new errorDriverDB("Hi ha problemes per instanciar la classe del driver de servei $className");

        $this->service = new $className($this->cadConn, $usrAdmin, $passwdAdmin);
        return $this->service;
    }

    public function endoll_db()
    {
        return ($this->dataBase);
    }

    public function desconnecta()
    {
        if(!$this->dataBase) return;
        $this->dataBase->Close();
    }

    public function reconnecta()
    {
        $this->desconnecta();
        $this->connecta();
    }

    public function __destruct()
    {
        $this->dataBase->Close();
    }
}