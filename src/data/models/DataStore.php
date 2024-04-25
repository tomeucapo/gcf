<?php

namespace gcf\data\models;

use gcf\cache\cachePlugin;

class DataStore
{
    private cachePlugin $storage;

    public function __construct(cachePlugin $storage)
    {
        $this->storage = $storage;
    }

    public function GetTables(): array
    {
        $tableList = $this->storage->get("TABLES");
        if ($tableList === false)
            return [];
        return $tableList;
    }

    public function CreateTable(string $tableName, string $description, array $fieldDefs): bool
    {
        $tableList = $this->GetTables();

        if (!array_key_exists($tableName, $tableList)) {
            $tableList[$tableName] = ["DESCRIPTION" => $description, "FIELDS" => $fieldDefs];
            $this->storage->set("TABLES", $tableList);
            return true;
        }
        return false;
    }

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
     * @throws \Exception
     */
    public function DescribeTable(string $tableName) : array
    {
        $tableList = $this->storage->get("TABLES");
        if ($tableList === false)
            throw new \Exception("$tableName not found!");

        if (!array_key_exists($tableName, $tableList))
            throw new \Exception("$tableName not found!");

        return $tableList[$tableName];
    }

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

    public function GetNextID(string $tableName) : int
    {
        return $this->storage->inc("TABSEQ:$tableName");
    }

    public function GetData(string $tableName) : array
    {
        $tableData = $this->storage->get("TABDAT:$tableName");

        if ($tableData === false)
            return [];

        return $tableData;
    }

    public function SetData(string $tableName, array $data) : mixed
    {
        return $this->storage->set("TABDAT:$tableName", $data);
    }
}