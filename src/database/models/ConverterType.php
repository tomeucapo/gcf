<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 6/19/2018
 * Time: 9:11 PM
 */

namespace gcf\database\models;

enum ConverterType : string
{
    case SQLInsert = "INSERT";
    case SQLUpdate = "UPDATE";
    case SQLDelete = "DELETE";
}