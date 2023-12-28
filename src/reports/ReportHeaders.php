<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 5/30/2018
 * Time: 12:03 PM
 */

namespace gcf\reports;

class ReportHeaders
{
    private array $defHeaders;
    private array $fields;

    public function __construct()
    {
        $this->defHeaders = [];
    }

    public function addHeader(ReportColumn $defColumn) : void
    {
        $this->defHeaders[] = $defColumn->getDefinition();
        $this->fields[] = $defColumn->getKey();
    }

    public function getDefinitions() : array
    {
        return $this->defHeaders;
    }

    public function getFields() : array
    {
        return $this->fields;
    }
}