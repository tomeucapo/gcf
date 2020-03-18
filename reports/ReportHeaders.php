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
    private $defHeaders, $fields;

    public function __construct()
    {
        $this->defHeaders = array();
    }

    public function addHeader(ReportColumn $defColumn)
    {
        $this->defHeaders[] = $defColumn->getDefinition();
        $this->fields[] = $defColumn->getKey();
    }

    public function getDefinitions()
    {
        return $this->defHeaders;
    }

    public function getFields()
    {
        return $this->fields;
    }
}