<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 6/19/2018
 * Time: 9:11 PM
 */

namespace gcf\database;

interface ConverterType
{
    const SQLInsert = "INSERT";
    const SQLUpdate = "UPDATE";
    const SQLDelete = "DELETE";
}