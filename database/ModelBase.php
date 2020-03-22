<?php

namespace gcf\database;

abstract class ModelBase extends \taulaBD implements ModelInterface
{
    /**
     * @var \Record
     */
    protected $record;

    /**
     * ModelBase constructor.
     */
    public function __construct()
    {
        $this->record = new \Record([],[]);
    }

    abstract public function Get($id) : \Record;
    abstract public function Insert(\Record $newRecord);
    abstract public function Update($id, \Record $newRecord);
    abstract public function Delete($id);
}