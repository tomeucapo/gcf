<?php

namespace gcf\data\models;

use Exception;
use gcf\cache\cachePlugin;

class DataStore
{
    private cachePlugin $storage;

    public function __construct(cachePlugin $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get all tables list and definitions
     * @return array
     */
    public function GetTables(): array
    {
        $tableList = $this->storage->get("TABLES");
        if ($tableList === false)
            return [];
        return $tableList;
    }

    /**
     * Create new table into data store
     * @param string $tableName
     * @param string $description
     * @param array $fieldDefs
     * @return bool
     */
    public function CreateTable(string $tableName, string $description, array $fieldDefs) : bool
    {
        $tableList = $this->GetTables();

        if (!array_key_exists($tableName, $tableList)) {
            $tableList[$tableName] = ["DESCRIPTION" => $description, "FIELDS" => $fieldDefs];
            $this->storage->set("TABLES", $tableList);
            return true;
        }
        return false;
    }

    /**
     * Modify table structure
     * @param string $tableName
     * @param string $description
     * @param array $fieldDefs
     * @return bool
     */
    public function ModifyTable(string $tableName, string $description, array $fieldDefs) : bool
    {
        $tableList = $this->GetTables();

        if (!array_key_exists($tableName, $tableList))
            return false;

        $tableList[$tableName] = ["DESCRIPTION" => $description, "FIELDS" => $fieldDefs];
        $this->storage->set("TABLES", $tableList);

        return true;
    }

    /**
     * Create basic index on selected field
     * @param string $tableName
     * @param string $fieldName
     * @return bool
     */
    public function CreateIndex(string $tableName, string $fieldName): bool
    {
        $tableList = $this->GetTables();

        if (!array_key_exists($tableName, $tableList))
            return false;

        if (!array_key_exists($fieldName, $tableList[$tableName]["FIELDS"]))
            return false;

        $idxData = [];
        foreach ($this->GetData($tableName) as $id => $record)
        {
            $idxData[$record->camps[$fieldName]] = $id;
        }

        $this->storage->set("TABIDX:$tableName:$fieldName", $idxData);
        return true;
    }

    /**
     * Return table definition
     * @throws Exception
     */
    public function DescribeTable(string $tableName) : array
    {
        $tableList = $this->storage->get("TABLES");
        if ($tableList === false)
            throw new Exception("$tableName not found!");

        if (!array_key_exists($tableName, $tableList))
            throw new Exception("$tableName not found!");

        return $tableList[$tableName];
    }

    /**
     * Drop table
     * @param string $tableName
     * @return bool
     */
    public function DropTable(string $tableName) : bool
    {
        $tableList = $this->storage->get("TABLES");
        if ($tableList === false)
            return false;

        if (!array_key_exists($tableName, $tableList))
            return false;

        $this->storage->delete("TABSEQ:$tableName");
        $this->storage->delete("TABDAT:$tableName");

        unset($tableList[$tableName]);

        $this->storage->set("TABLES", $tableList);

        return true;
    }

    /**
     * Get next ID of table
     * @param string $tableName
     * @return int
     */
    public function GetNextID(string $tableName) : int
    {
        return $this->storage->inc("TABSEQ:$tableName");
    }

    /**
     * Get all data of selected table
     * @param string $tableName
     * @return array
     */
    public function GetData(string $tableName) : array
    {
        $tableData = $this->storage->get("TABDAT:$tableName");

        if ($tableData === false)
            return [];

        return $tableData;
    }

    /**
     * Store all data of selected table
     * @param string $tableName
     * @param array $data
     * @return mixed
     */
    public function SetData(string $tableName, array $data) : mixed
    {
        return $this->storage->set("TABDAT:$tableName", $data);
    }
}