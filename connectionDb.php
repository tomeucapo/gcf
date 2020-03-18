<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 5/30/2018
 * Time: 11:35 AM
 */

require_once "database/base_dades.php";
require_once "connectionTypeError.php";

use gcf\database\errorDriverDB;

class connectionDb
{
    private $type;
    public $connStr, $auth;
    public $user, $passwd, $role;

    /**
     * @var base_dades
     */
    private $conn;

    /**
     * connectionDb constructor.
     * @param $config
     * @throws connectionTypeError
     */
    public function __construct($config)
    {
        switch($config->type)
        {
            case "firebird":$db_host = $config->params->host.":";
                $db_host.= $config->params->path.$config->params->dbname;
                break;

            case "postgres":$db_host = "host=".$config->params->host." dbname=".$config->params->dbname;
                break;

            case "oracle":$db_host = "(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)";
                $db_host.= "(HOST = {$config->params->host})(PORT = {$config->params->port})))";
                $db_host.= " (CONNECT_DATA = (SERVER = DEDICATED)(SID = {$config->params->dbname})))";
                break;

            default:throw new connectionTypeError("El tipus {$config->type} no esta soportat!");
                break;
        }
        $this->type = $config->type;
        $this->auth = $config->auth;

        if ( $config->auth !== "session" )
        {
            $this->user = $config->params->username;
            $this->passwd = $config->params->password;
        }

        $this->connStr = $db_host;
    }

    /**
     * @param string $mode Mode of connection P = Persistent / N = Non-persistent
     * @return base_dades
     * @throws errorDriverDB
     */
    public function getConnection($mode="P")
    {
        if (!isset($this->conn))
            $this->conn = new base_dades($this->connStr, $this->user, $this->passwd, $mode, $this->type, $this->role);

        return $this->conn;
    }

    public function close()
    {
        if (isset($this->conn)) {
            $this->conn->desconnecta();
            unset($this->conn);
        }
    }
}
