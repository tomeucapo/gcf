<?php

namespace gcf\database\models;

use gcf\database\drivers\errorQuerySQL;
use gcf\database\errorDriverDB;

trait DataMapperRecord
{
    /**
     * @param $pk
     * @param Record $dataRecord
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    public function updateRecord($pk, Record $dataRecord): void
    {
        $this->camps = $dataRecord->camps;
        $this->Modifica($pk);
    }

    /**
     * @param Record $dataRecord
     * @return mixed
     * @throws errorDriverDB
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    public function newRecord(Record $dataRecord): mixed
    {
        $this->camps = $dataRecord->camps;
        return $this->Nou();
    }
}