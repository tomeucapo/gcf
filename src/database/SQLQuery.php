<?php
/**
 * Database SQL query class manager
 * User: tomeu
 * Date: 4/5/2018
 * Time: 11:41 AM
 */

namespace gcf\database;

use gcf\cache\cachePlugin;
use PDO;

class SQLQuery
{
    /**
     * Query object
     * @var PDO
     */
    private $consulta;

    /**
     * @var \PDOStatement
     */
    private $stmt;

    /**
     * @var cachePlugin
     */
    private $cache;

    private $autoFlush;

    private $queryObj, $firmaLastQuery, $rowCount;
    public $row, $assoc;

    /**
     * @var bool
     */
    private $initialGet;

    /**
     * consulta_sql constructor.
     * @param DatabaseConnector $db
     * @param cachePlugin|null $cache
     * @param bool $autoFlush
     */
    public function __construct(DatabaseConnector $db, cachePlugin $cache=null, $autoFlush=false)
    {
        $this->consulta = $db->endoll_db();

        if (!empty($cache))
            $this->cache = $cache;

        $this->autoFlush = $autoFlush;
        $this->rowCount = -1;
        $this->initialGet = false;
    }

    private function extractFieldTypes()
    {
        $rowTypes = [];
        for ($i = 0; $i < count($this->consulta->row); $i++)
        {
            $rowTypes[] = ["TYPE" => $this->consulta->GetFieldType($i),
                        "NAME" => $this->consulta->GetFieldName($i)];
        }
        return $rowTypes;
    }

    /**
     * @param $query
     * @param bool $assoc
     * @return null
     * @throws \errorQuerySQL
     */
    public function fer_consulta($query, $assoc=false)
    {
        $this->consulta->assoc = $assoc;
        $resCons = null;

        if (isset($this->cache))
        {
            $objectName = "G";
            if (preg_match("/^[ \n\r\t]*(select|SELECT).+(from|FROM)[ \n\r\t]*([a-zA-Z0-9_]+)[ \n\r\t]*/", $query, $queryParts)) {
                $objectName = $queryParts[3];
            }
            $this->firmaLastQuery = "QUERY:$objectName:".sha1($query);
            if (!($this->queryObj = $this->cache->get($this->firmaLastQuery)))
            {
                $this->initialGet = true;
                $resCons = $this->consulta->Query($query);
                $queryObj = new \stdClass;
                $queryObj->consulta = $this->consulta;
                $queryObj->rowTypes = $this->extractFieldTypes();
                $queryObj->allRows = [ $this->stmt->fetch($this->assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM ) ];
                $this->cache->set($this->firmaLastQuery, $queryObj);
                $this->queryObj = $queryObj;
            } else {
                unset($this->consulta);
                $this->consulta = $this->queryObj->consulta;
                $this->consulta->row = $this->queryObj->allRows[++$this->rowCount];
            }
        } else
            $resCons = $this->consulta->Query($query);

        $this->row = $this->consulta->row;
        return $resCons;
    }

    /**
     * @param bool $assoc
     * @return bool|null
     * @throws \errorQuerySQL
     */
    public function executa($assoc=false)
    {
        // TODO
        return false;
    }

    public function Eof()
    {
        if (isset($this->cache))
        {
            if ($this->initialGet)
                return $this->consulta->Eof();

            if ($this->rowCount < count($this->queryObj->allRows))
            {
                $this->row = $this->queryObj->allRows[$this->rowCount];
                return false;
            }
            return true;
        } else {
            $this->row = $this->consulta->row;
            return $this->consulta->Eof();
        }
    }

    public function Skip()
    {
        if (isset($this->cache))
        {
            if ($this->initialGet)
            {
                $row = $this->stmt->fetch($this->assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM );
                if ($row !== false) {
                    $this->queryObj->allRows[] = $row;
                }
                $this->row = $row;
            } else {
                $this->row = $this->queryObj->allRows[$this->rowCount++];
            }
        }
        else
        {
            $this->row = $this->stmt->fetch($this->assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM );
        }
    }

    public function Record()
    {
        return 0;
        /*
        if (isset($this->cache))
        {
            if ($this->initialGet)
                return $this->consulta->Record();
            return ($this->rowCount);
        }

        return $this->consulta->Record();
    */
    }

    public function LastRecord()
    {
        if (isset($this->cache))
        {
            if ($this->initialGet)
                return $this->stmt->rowCount();
            return (count($this->queryObj->allRows));
        }

        return $this->stmt->rowCount();
    }
/*
    public function TipusField($numField)
    {

        if (isset($this->cache))
        {
            if ($numField>count($this->queryObj->rowTypes))
                return null;
            return $this->queryObj->rowTypes[$numField]["TYPE"];
        }

        return $this->stmt->getColumnMeta($numField);
    }

    public function NomField($numField)
    {
        if (isset($this->cache))
        {
            if ($numField>count($this->queryObj->rowTypes))
                return null;
            return $this->queryObj->rowTypes[$numField]["NAME"];
        }

        return $this->consulta->GetFieldName($numField);
    }

    public function RelacioField($numRow)
    {
        //return $this->consulta->GetFieldRelation($numRow);
    }
*/
    public function NumFields()
    {
        return $this->stmt->columnCount();
    }

    public function carregarBLOB($blobID)
    {
       //  return $this->consulta->LoadFromBLOB($blobID);
    }

    public function guardaImatge($fileName)
    {
       // return $this->consulta->StoreFileToBLOB($fileName);
    }

    public function iniciTrans()
    {
        return $this->consulta->beginTransaction();
    }

    public function ferRollback()
    {
        return $this->consulta->Rollback();
    }

    public function ferCommit()
    {
        return $this->consulta->Commit();
    }

    public function tanca_consulta()
    {
        if (isset($this->cache)) {
            if ($this->initialGet) {
                $this->cache->set($this->firmaLastQuery, $this->queryObj);
            }
        }
    }

    public function nextID($genID)
    {
        return $this->consulta->NextID($genID);
    }

    public function getCacheKey()
    {
        return $this->firmaLastQuery;
    }

    public function lastError()
    {
        return $this->consulta->LastError();
    }

    public function __destruct()
    {
        if (isset($this->cache) && !$this->initialGet && $this->autoFlush) {
            $this->cache->delete($this->firmaLastQuery);
        }
    }
}