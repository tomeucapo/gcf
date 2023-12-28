<?php

namespace gcf\database;

use gcf\database\models\Record;
use gcf\database\models\taulaBD;

abstract class ModelBase extends taulaBD implements ModelInterface
{
    /**
     * @var Record
     */
    protected Record $record;

    /**
     * ModelBase constructor.
     * @throws errorDriverDB
     */
    public function __construct(base_dades $db, string $nomTaula, $pk, $tipusPK)
    {
        parent::__construct($db, $nomTaula, $pk, $tipusPK);
        $this->record = new Record([],[]);
    }

    abstract public function Get($id) : Record;
    abstract public function Insert(Record $newRecord);
    abstract public function Update($id, Record $newRecord);
    abstract public function Delete($id);
}