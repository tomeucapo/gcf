<?php
interface ModelInterface
{
    public function Insert(Record $newRecord);
    public function Update($id, Record $newRecord);
    public function Delete($id);
    public function NextId();
}