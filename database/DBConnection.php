<?php


namespace gcf\database;


interface DBConnection
{
    public function Open();
    public function Close();
    public function lastError();
}