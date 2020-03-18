<?php

namespace gcf\database;

use Record;

abstract class ModelBase implements \ModelInterface
{
    /**
     * @var Record
     */
    protected $record;

    public function __construct()
    {
        $this->record = new Record([],[]);
    }

    abstract public function Insert(Record $newRecord);
    abstract public function Update($id, Record $newRecord);
    abstract public function Delete($id);
    abstract public function NextId();
}