<?php
/**
 * DatabaseConnector class
 * User: tomeu
 * Date: 4/5/2018
 * Time: 11:38 AM
 */

namespace gcf\database;

use PDO;

class DatabaseConnector
{
    /**
     * Database connection object
     * @var PDO
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

        if (!empty($my_role) && $drv === "firebird")
            $cadConn.=",role=$my_role";

        $this->dataBase = new PDO("{$drv}:{$cadConn}", $user, $passwd);

        $this->drv = $drv;
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
        $this->dataBase = new PDO("{$this->drv}:{$this->cadConn}", $this->user, $this->passwd);
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

    public function endoll_db() : PDO
    {
        return ($this->dataBase);
    }

    public function desconnecta()
    {
        if(!$this->dataBase) return;
        $this->dataBase = null;
    }

    public function reconnecta()
    {
        $this->desconnecta();
        $this->connecta();
    }
}