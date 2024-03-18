<?php
/**
 * Iterador ResultSet per poder iterar amb un foreach.
 * User: tomeu
 * Date: 8/18/2017
 * Time: 2:40 PM
 */

namespace gcf\database\models;

use gcf\database\SQLQuery;
use gcf\database\drivers\errorQuerySQL;
use Iterator;

/**
 * Class ResultSet
 */
class ResultSet implements Iterator
{
    private int $position = 0;
    private SQLQuery $query;
    private mixed $pk;
    protected array $types;

    /**
     * @var bool BLOB Loading when get current record. By default, is disabled
     */
    private bool $BLOBLoad;

    public function __construct(SQLQuery $query, mixed $pk, bool $BLOBLoad=false)
    {
        $this->query = $query;
        $this->pk = $pk;
        $this->types = [];
        $this->BLOBLoad = $BLOBLoad;
    }

    /**
     * @throws errorQuerySQL
     */
    public function rewind() : void
    {
        $this->position = 0;

        // TODO: No funciona correctament si ho empram amb una query de cache

        $this->query->executa(true);

        if (!$this->query->row)
            return;

        // Get all field types
        $i = 0;
        foreach ($this->query->row as $nomCamp => $valor)
        {
            if (!array_key_exists($nomCamp, $this->types)) {
                $this->types[$nomCamp] = ["type" => $this->query->TipusField($i),
                    "length" => $this->query->LenField($i)];
            }
            $i++;
        }
    }

    /**
     * Get current record
     * @return Record
     */
    public function current() : Record
    {
        $camps = $this->query->row;

        if ($this->BLOBLoad)
        {
            foreach ($camps as $nomCamp => $valor) {
                if ($this->types[$nomCamp]["type"] === 'BLOB' && !empty($valor)) {
                    $camps[$nomCamp] = $this->query->carregarBLOB($valor);
                }
            }
        }

        return new Record($camps, $this->types);
    }


    public function key() : mixed
    {
        $keyStr = $this->position;

        if (is_array($this->pk))
        {
            $keyParts = [];
            foreach ($this->pk as $key) {
                if (key_exists(strtoupper($key), $this->query->row))
                    $keyParts[] = $this->query->row[strtoupper($key)];
                else if (key_exists($key, $this->query->row))
                    $keyParts[] =  $this->query->row[$key];
            }
            $keyStr = implode(":", $keyParts);
        } else if (is_string($this->pk)) {
            if (key_exists(strtoupper($this->pk), $this->query->row))
                $keyStr = $this->query->row[strtoupper($this->pk)];
            else
                if (key_exists($this->pk, $this->query->row))
                    $keyStr = $this->query->row[$this->pk];
        }

        return $keyStr;
    }

    /**
     * Next element of iterator, in this case fetch next query row
     */
    public function next() : void
    {
        $this->query->Skip();
        ++$this->position;
    }

    /**
     * Detects if is the end of iterator
     * @return bool
     */
    public function valid(): bool
    {
        return !$this->query->Eof();
    }
}
