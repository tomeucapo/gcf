<?php
namespace gcf\database\drivers;

/**
 * Class dataBaseConn
 */
abstract class dataBaseConn implements DBConnection
{
    /**
     * @var string Connection string properties
     */
	protected string $cadConn;

    /**
     * @var string Driver identification string
     */
	protected string $drvId;

    /**
     * @var mixed Driver resource connection
     */
	public $connDb;

    /**
     * @return mixed
     * @throws errorDatabaseAutentication
     */
	abstract public function Open();
	abstract public function Close();
	abstract public function lastError();
	
    public function __destruct()
    {
	    $this->Close();
    }
}
