<?php

class errorDatabaseDriver extends Exception {};

class errorDatabaseAutentication extends Exception {};

class errorDatabaseConnection extends Exception 
{
      public function __construct($message = null, $code = 0)
      {
             if(!$message)
               throw new $this('Desconegut '.get_class($this));

             parent::__construct($message, $code);
      }
}

/**
 * Class dataBaseConn
 */
abstract class dataBaseConn implements gcf\database\DBConnection
{
    /**
     * @var string Connection string properties
     */
	protected $cadConn;

    /**
     * @var string Driver identification string
     */
	protected $drvId;

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