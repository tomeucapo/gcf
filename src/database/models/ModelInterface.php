<?php
namespace gcf\database\models;

interface ModelInterface
{
    public function Get($id) : Record;
    public function Insert(Record $newRecord);
    public function Update($id, Record $newRecord);
    public function Delete($id);
    public function NextId();
}