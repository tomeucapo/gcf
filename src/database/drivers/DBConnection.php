<?php


namespace gcf\database\drivers;


interface DBConnection
{
    public function Open();
    public function Close();
    public function lastError();
}
