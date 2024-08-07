<?php

namespace gcf\database\drivers;

abstract class queryBase 
{
	protected dataBaseConn $dataBase;
    protected mixed $connDb;

	protected int $rowActual, $rows;
	protected $result;
	protected $error;
	protected $blobID;

    public $row, $assoc, $query;

	public function __construct(dataBaseConn $db)
	{
		$this->dataBase = $db;
		$this->connDb = $db->connDb;
		$this->rowActual = $this->rows = -1;
		$this->blobID = null;
        $this->assoc = false;
	}

    /**
     * @return mixed
     * @throws errorQuerySQL
     */
	abstract public function Execute();

	abstract public function Skip();
	abstract public function NumFields();
	abstract public function Close();
	abstract public function BeginTrans();
    abstract public function Commit($idTrans);
    abstract public function Rollback($idTrans);
    abstract public function NextID($generatorID);

    // Field properties methods
    abstract public function GetFieldType($field);
    abstract public function GetFieldName($field);
    abstract public function GetFieldLength($field);
    abstract public function GetFieldRelation($field);

    // BLOB abstract methods
    abstract public function LoadFromBLOB($rowBlob);
    abstract protected function StoreBLOB($fileDescriptor);

    public function GetConn() : dataBaseConn
    {
           return($this->connDb);
    }

    public function ChangeConnection(dataBaseConn $connDb) : void
    {
           $this->connDb = $connDb;
    }

    /**
     * @param $queryStr
     * @return mixed
     * @throws errorQuerySQL
     */
	public function Query($queryStr)
	{
		$this->query = $queryStr;
		return $this->Execute();
	}
	
    public function Go($numRow) 
    {
           if ($this->rowActual == $numRow) return;
           $this->rowActual = $numRow - 1;
           $i = 0;
	       while($i < $numRow) 
	       {
		         $i++;
                 $this->Skip();
	       }
    }

    public function Eof() 
    {
           return($this->rows == 0 || $this->rowActual >= $this->rows);
    }

    public function Record() 
    {
           return $this->rowActual;
    }
	
    public function LastRecord() 
    {
           return $this->rows;
    }

    public function LastError()
    {
            return $this->error;
    }

    public function StoreFileToBLOB(string $nomFitxer) : bool
    {
           if(!file_exists($nomFitxer)) 
              return false;

           $fd = fopen($nomFitxer,"r");
           $this->blobID = $this->StoreBLOB($fd);
	       fclose($fd);

           return true;
	}

    public function __toString()
    {
           return $this->query;
    }

	public function __destruct()
	{
           $this->Close();
	}
}
