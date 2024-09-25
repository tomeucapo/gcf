<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 5/30/2018
 * Time: 11:55 AM
 */

namespace gcf\reports;

use stdClass;

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
    private array $validProps = ["key","label","width","formatter","className","filter", "sortable", "children"];

    private array $column = [];

    private string $key;

    public string $filter = "";

    private ReportColumnType $type;

    public function __construct(ReportColumnType $type=ReportColumnType::GENERAL_FORMAT)
    {
        $this->type = $type;
    }

    public function setFormat(ReportColumnType $type) : void
    {
        $this->type = $type;
    }

    public function getFormat() : ReportColumnType
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

    /**
     * @param $property
     * @param $value
     * @return void
     * @throws UnknownProperty
     */
    public function __set($property, $value)
    {
        if (!in_array($property, $this->validProps))
            throw new UnknownProperty("$property is not valid column property");

        if (strtoupper($property)==='KEY')
            $this->key = $value;

        if (strtoupper($property)==='FILTER')
            $this->filter = $value;

        $this->column[$property] = $value;
    }

    public function getDefinition() : stdClass
    {
        $def = new stdClass();
        $def->type = $this->type;
        $def->props = $this->column;
        return $def;
    }

    public function getKey() : string
    {
        return $this->key;
    }
}
