<?php
/**
 *
 */

namespace gcf\database;

use gcf\connectionTypeError;
use Laminas\Config\Config;

class ConnectionDb
{
    private string $type;

    /**
     * @var string Specific connection database string
     */
    public string $connStr;

    /**
     * @var string Authentication type if is session or by configuration file
     */
    public string $auth;

    public string $user, $passwd;

    /**
     * @var string Database user role name
     */
    public string $role;

    /**
     * @var DatabaseConnector
     */
    private DatabaseConnector $conn;

    /**
     * connectionDb constructor.
     * @param Config $config
     * @throws connectionTypeError
     */
    public function __construct(Config $config)
    {
        switch ($config->type)
        {
            case "firebird":
                $db_host = $config->params->host . ":";
                $db_host .= $config->params->path . $config->params->dbname;
                break;

            case "postgres":
                $db_host = "host=" . $config->params->host . " dbname=" . $config->params->dbname;
                break;

            case "oracle":
                $db_host = "(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)";
                $db_host .= "(HOST = {$config->params->host})(PORT = {$config->params->port})))";
                $db_host .= " (CONNECT_DATA = (SERVER = DEDICATED)(SID = {$config->params->dbname})))";
                break;

            default:
                throw new connectionTypeError("El tipus $config->type no esta soportat!");
        }
        $this->type = $config->type;
        $this->auth = $config->auth;

        if ($config->auth !== "session") {
            $this->user = $config->params->username;
            $this->passwd = $config->params->password;
        }

        $this->connStr = $db_host;
    }

    /**
     * @param ConnectionMode $mode Mode of connection P = Persistent / N = Non-persistent
     * @return DatabaseConnector
     * @throws errorDriverDB
     */
    public function getConnection(ConnectionMode $mode = ConnectionMode::NORMAL) : DatabaseConnector
    {
        if (!isset($this->conn))
            $this->conn = new DatabaseConnector($this->connStr, $this->user, $this->passwd, $mode, $this->type, $this->role);
        return $this->conn;
    }

    /**
     * Reconnect this database connection
     * @throws errorDriverDB
     */
    public function reconnect() : void
    {
        $this->close();
        $this->getConnection();
    }

    /**
     * Close this database connection
     * @return void
     */
    public function close() : void
    {
        if (isset($this->conn))
        {
            $this->conn->desconnecta();
            unset($this->conn);
        }
    }
}
