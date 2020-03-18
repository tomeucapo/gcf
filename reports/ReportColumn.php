<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 5/30/2018
 * Time: 11:55 AM
 */

namespace gcf\reports;

/**
 * Class ReportColumn
 * @property string key Key of field, is a field name.
 * @property string label Visual label of column.
 * @property integer width Width in pixels of column.
 * @property string className CSS Class name of column, applicable to each cell.
 * @property string formatter Function name of formatter for each cell.
 * @property boolean filter Define column filterable or not.
 * @property boolean sortable Define sortable column or not.
 */

class ReportColumn
{
    const GENERAL_FORMAT = 0;
    const NUMBER_FORMAT = 1;

    private $column, $validProps, $key;
    private $filter;
    private $type;

    public function __construct($type=self::GENERAL_FORMAT)
    {
        $this->filter = false;
        $this->type = $type;
        $this->column = [];
        $this->validProps = array("key","label","width","formatter","className","filter","sortable");
    }

    public function setFormat($type)
    {
        $this->type = $type;
    }

    public function getFormat($type)
    {
        return $this->type;
    }

    /**
     * @param $property
     * @return mixed|null
     * @throws UnknownProperty
     */
    public function __get($property)
    {
        if (!in_array($property, $this->validProps))
            throw new UnknownProperty("$property is not valid column property");

        if (isset($this->column[$property]))
            return $this->column[$property];
        return null;
    }

    public function __set($property, $value)
    {
        if (!in_array($property, $this->validProps))
            throw new UnknownProperty("$property is not valid column property");

        if (strtoupper($property)=='KEY')
            $this->key = $value;

        if (strtoupper($property)=='FILTER')
            $this->filter = $value;

        $this->column[$property] = $value;
    }

    public function getDefinition()
    {
        $def = new \stdClass();
        $def->type = $this->type;
        $def->props = $this->column;
        return $def;
    }

    public function getKey()
    {
        return $this->key;
    }
}
