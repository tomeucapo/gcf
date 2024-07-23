<?php
namespace gcf\database\drivers\firebird;

use gcf\database\ConnectionMode;
use gcf\database\DataBaseConnProps;
use gcf\database\drivers\dataBaseConn;
use gcf\database\drivers\errorDatabaseAutentication;
use gcf\database\drivers\errorDatabaseConnection;
use gcf\database\drivers\errorDatabaseDriver;

class Connector extends dataBaseConn
{
    private DataBaseConnProps $props;

    private string $funcConn;
    private string $role;

    /**
     * baseDadesFirebird constructor.
     * @param DataBaseConnProps $props
     * @param ?string $my_role
     * @param ConnectionMode $mode
     * @throws errorDatabaseAutentication
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */

    public function __construct(DataBaseConnProps $props, ?string $my_role = "", ConnectionMode $mode = ConnectionMode::NORMAL)
    {
        $this->funcConn = ($mode === ConnectionMode::NORMAL) ? "ibase_connect" : "ibase_pconnect";
        if (!function_exists($this->funcConn))
            throw new errorDatabaseDriver("No hi ha instal.lat el driver de Firebird!");

        $this->role = $my_role;
        $this->drvId = "INTERBASE";
        $this->props = $props;

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
            $this->connDb = @$funcConn($this->props->cadConn, $this->props->user, $this->props->passwd, 'UTF8', 0, 3);
        else
            $this->connDb = @$funcConn($this->props->cadConn, $this->props->user, $this->props->passwd, 'UTF8', 0, 3, $this->role);

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
