<?php

class dataBaseServiceFirebird extends dataBaseService
{
    /**
     * dataBaseServiceFirebird constructor.
     * @param $cadConn
     * @param $user
     * @param $passwd
     * @throws errorDatabaseDriver
     * @throws errorDatabaseService
     */
    public function __construct($cadConn, $user, $passwd)
    {
           $this->info->name = "Firebird Database";

           if (!function_exists("ibase_service_attach"))
              throw new errorDatabaseDriver("No hi ha instal.lat el driver de Firebird!");

           $hostComp = preg_split("/:/", $cadConn);

           parent::__construct($hostComp[0], $user, $passwd);
           if (!($this->service = @ibase_service_attach($this->host, $this->user, $this->passwd)))
              throw new errorDatabaseService(ibase_errmsg());

           $this->info->version = ibase_server_info($this->service, IBASE_SVC_SERVER_VERSION);
           $this->info->platform = ibase_server_info($this->service, IBASE_SVC_IMPLEMENTATION);
    }

    public function Close()
    {
           if ($this->service)
              ibase_service_detach($this->service);
    }

    /**
     * @param $userName
     * @param $passwd
     * @throws errorAddUser
     */
    public function AddUser($userName, $passwd, $cn)
    {
           if (!($result = ibase_add_user($this->service, $userName, $passwd, $cn)))
               throw new errorAddUser(ibase_errmsg());
    }

    /**
     * @param $userName
     */
    public function DelUser($userName)
    {
           if (!ibase_delete_user($this->service, $userName))
               throw new errorDelUser(ibase_errmsg());
    }
}
