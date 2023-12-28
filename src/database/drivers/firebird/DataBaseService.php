<?php

namespace gcf\database\drivers\firebird;

use gcf\database\drivers\errorAddUser;
use gcf\database\drivers\errorDatabaseDriver;
use gcf\database\drivers\errorDatabaseService;
use gcf\database\drivers\DataBaseService as DataBaseServiceBase;
use gcf\database\drivers\errorDelUser;

class DataBaseService extends DataBaseServiceBase
{
    /**
     * dataBaseServiceFirebird constructor.
     * @param string $cadConn
     * @param string $user
     * @param string $passwd
     * @throws errorDatabaseDriver
     * @throws errorDatabaseService
     */
    public function __construct(string $cadConn, string $user, string $passwd)
    {
           if (!function_exists("ibase_service_attach"))
              throw new errorDatabaseDriver("No hi ha instal.lat el driver de Firebird!");

           $hostComp = explode(":", $cadConn);

           parent::__construct($hostComp[0], $user, $passwd);

           $this->info->name = "Firebird Database";
           if (!($this->service = @ibase_service_attach($this->host, $this->user, $this->passwd)))
              throw new errorDatabaseService(ibase_errmsg());

           $this->info->version = ibase_server_info($this->service, IBASE_SVC_SERVER_VERSION);
           $this->info->platform = ibase_server_info($this->service, IBASE_SVC_IMPLEMENTATION);
    }

    public function Close() : void
    {
           if ($this->service)
              ibase_service_detach($this->service);
    }

    /**
     * @param string $userName
     * @param string $passwd
     * @param string $cn
     * @throws errorAddUser
     */
    public function AddUser(string $userName, string $passwd, string $cn)
    {
           if (!($result = ibase_add_user($this->service, $userName, $passwd, $cn)))
               throw new errorAddUser(ibase_errmsg());
    }

    /**
     * @param string $userName
     */
    public function DelUser(string $userName) : void
    {
           if (!ibase_delete_user($this->service, $userName))
               throw new errorDelUser(ibase_errmsg());
    }
}
