<?php

namespace gcf\data\models;

use gcf\database\models\Record;

class DataModelStore implements ModelInterface
{
    private string $tableName;
    private DataStore $storage;

    public function __construct(string $tableName, DataStore $store)
    {
        $this->storage = $store;
        $this->tableName = $tableName;
    }

    public function Create(array $fields, string $description) : bool
    {
        return $this->storage->CreateTable($this->tableName, $description, $fields);
    }

    /**
     * @throws \Exception
     */
    public function Describe() : array
    {
       return $this->storage->DescribeTable($this->tableName);
    }

    public function Drop() : bool
    {
       return $this->storage->DropTable($this->tableName);
    }

    /**
     * Get next sequence number for table ID
     * @return int
     */
    public function NextId() : int
    {
           return $this->storage->GetNextID($this->tableName);
    }

    /**
     * Insert new record in table
     * @throws \Exception
     */
    public function Insert(Record $newRecord): int
    {
            $tableData = $this->storage->GetData($this->tableName);

            $nextID = $this->NextId();
            if (!array_key_exists($nextID, $tableData))
                $tableData[$nextID] = $newRecord;
            else throw new \Exception("Primary key $nextID exists into $this->tableName");

            $this->storage->SetData($this->tableName, $tableData);

            return $nextID;
    }

    /**
     * Delete record from table
     * @param int $id
     * @return bool
     */
    public function Delete($id) : bool
    {
        $tableData = $this->storage->GetData($this->tableName);
        if (!empty($tableData))
        {
            if (array_key_exists($id, $tableData))
            {
                unset($tableData[$id]);
                $this->storage->SetData($this->tableName, $tableData);
                return true;
            }
        }
        return false;
    }

    /**
     * Get record from table
     * @param $id
     * @return Record|null
     */
    public function Get($id) : ?Record
    {
        $tableData = $this->storage->GetData($this->tableName);
        if (!empty($tableData))
        {
            if (array_key_exists($id, $tableData))
                return $tableData[$id];
        }
        return null;
    }

    /**
     * Update record from table
     * @param $id
     * @param Record $newRecord
     * @return bool
     */
    public function Update($id, Record $newRecord) : bool
    {
        $tableData = $this->storage->GetData($this->tableName);

        if (array_key_exists($id, $tableData))
        {
            $tableData[$id] = $newRecord;
            $this->storage->SetData($this->tableName, $tableData);
            return true;
        }

        return false;
    }

    public function GetAll() : array
    {
        return $this->storage->GetData($this->tableName);
    }
}