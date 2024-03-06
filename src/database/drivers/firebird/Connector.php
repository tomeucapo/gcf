<?php
namespace gcf\database\drivers\firebird;

use gcf\database\ConnectionMode;
use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseAutentication;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    private string $funcConn;
    private string $role;
    private string $user, $pass;

    /**
     * baseDadesFirebird constructor.
     * @param string $cadConn
     * @param string $user
     * @param string $passwd
     * @param ?string $my_role
     * @param ConnectionMode $mode
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     * @throws errorDatabaseAutentication
     */

    public function __construct(string $cadConn, string $user, string $passwd, ?string $my_role = "", ConnectionMode $mode = ConnectionMode::NORMAL)
    {
        $this->funcConn = ($mode === ConnectionMode::NORMAL) ? "ibase_connect" : "ibase_pconnect";
        if (!function_exists($this->funcConn))
            throw new errorDatabaseDriver("No hi ha instal.lat el driver de Firebird!");

        $this->user = $user;
        $this->pass = $passwd;

        $this->role = $my_role;
        $this->cadConn = $cadConn;
        $this->drvId = "INTERBASE";

        $this->Open();
    }

    /**
     * @throws errorDatabaseConnection
     * @throws errorDatabaseAutentication
     */
    public function Open() : void
    {
        $funcConn = $this->funcConn;
        if (empty($this->role))
            $this->connDb = @$funcConn($this->cadConn, $this->user, $this->pass, 'UTF8', 0, 3);
        else
            $this->connDb = @$funcConn($this->cadConn, $this->user, $this->pass, 'UTF8', 0, 3, $this->role);

        if (!$this->connDb)
        {
            $error = $this->lastError();
            if (str_contains($error, "Your user name and password are not defined"))
                throw new errorDatabaseAutentication("Error d'autenticació: ".$error);

            throw new errorDatabaseConnection("Error al connectar a $this->cadConn: $error");
        }
    }

    public function Close() : void
    {
        if ($this->connDb)
            @ibase_close($this->connDb);
    }

    public function lastError() : string
    {
        return (ibase_errmsg());
    }
}
