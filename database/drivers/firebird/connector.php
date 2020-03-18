<?php

class baseDadesFirebird extends dataBaseConn
{
    /**
     * baseDadesFirebird constructor.
     * @param $cadConn
     * @param $user
     * @param $passwd
     * @param string $my_role
     * @param string $mode
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     */
    public function __construct($cadConn, $user, $passwd, $my_role = "", $mode = "N")
    {
        $funcConn = ($mode === "N") ? "ibase_connect" : "ibase_pconnect";
        if (!function_exists($funcConn))
            throw new errorDatabaseDriver("No hi ha instal.lat el driver de Firebird!");

        if (!$my_role)
            $this->connDb = @$funcConn($cadConn, $user, $passwd, 'UTF8');
        else
            $this->connDb = @$funcConn($cadConn, $user, $passwd, 'UTF8', 0, 3, $my_role);

        if (!$this->connDb) {
            throw new errorDatabaseConnection("Error al connectar a $cadConn " . $this->lastError());
        }

        $this->cadConn = $cadConn;
        $this->drvId = "INTERBASE";
    }

    public function Close()
    {
        if ($this->connDb)
            @ibase_close($this->connDb);
    }

    public function lastError()
    {
        return (ibase_errmsg());
    }
}
