<?php

namespace gcf\database;

use gcf\database\models\Record;
use gcf\database\models\DataMapper;

abstract class ModelBase extends DataMapper implements ModelInterface
{
    /**
     * @var Record
     */
    protected Record $record;

    /**
     * ModelBase constructor.
     * @throws errorDriverDB
     */
    public function __construct(DatabaseConnector $db, string $nomTaula, $pk, $tipusPK)
    {
        parent::__construct($db, $nomTaula, $pk, $tipusPK);
        $this->record = new Record([],[]);
    }

    abstract public function Get($id) : Record;
    abstract public function Insert(Record $newRecord);
    abstract public function Update($id, Record $newRecord);
    abstract public function Delete($id);
}