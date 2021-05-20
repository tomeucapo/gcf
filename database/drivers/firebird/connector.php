<?php

class baseDadesFirebird extends dataBaseConn
{
    private $funcConn;

    private $role;

    private $user, $pass;

    /**
     * baseDadesFirebird constructor.
     * @param $cadConn
     * @param $user
     * @param $passwd
     * @param string $my_role
     * @param string $mode
     * @throws errorDatabaseConnection
     * @throws errorDatabaseDriver
     * @throws errorDatabaseAutentication
     */
    public function __construct($cadConn, $user, $passwd, $my_role = "", $mode = "N")
    {
        $this->funcConn = ($mode === "N") ? "ibase_connect" : "ibase_pconnect";
        if (!function_exists($this->funcConn))
            throw new errorDatabaseDriver("No hi ha instal.lat el driver de Firebird!");

        $this->user = $user;
        $this->pass = $passwd;

        $this->cadConn = $cadConn;
        $this->role = $my_role;
        $this->cadConn = $cadConn;
        $this->drvId = "INTERBASE";

        $this->Open();
    }

    /**
     * @throws errorDatabaseConnection
     * @throws errorDatabaseAutentication
     */
    public function Open()
    {
        $funcConn = $this->funcConn;
        if (!$this->role)
            $this->connDb = @$funcConn($this->cadConn, $this->user, $this->pass, 'UTF8', 0, 3);
        else
            $this->connDb = @$funcConn($this->cadConn, $this->user, $this->pass, 'UTF8', 0, 3, $this->role);

        if (!$this->connDb)
        {
            $error = $this->lastError();
            if (  preg_match("/Your user name and password are not defined/", $error) )
                throw new errorDatabaseAutentication("Error d'autenticació: ".$error);

            throw new errorDatabaseConnection("Error al connectar a $this->cadConn: $error");
        }
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
