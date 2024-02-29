<?php

namespace gcf\database\models;

/**
 * Class Record
 * Store fields of record constructed by ResultSet (on the fly)
 */
class Record
{
    /**
     * @var array
     */
    public array $camps;

    /**
     * @var array
     */
    protected array $types;

    public function __construct(array $dataIn = [], ?array $types = [])
    {
        $this->camps = array_map(function ($value) {
            if (is_string($value) && mb_detect_encoding($value, 'UTF-8', true) !== 'UTF-8')
                return utf8_encode($value);
            return $value;
        }, $dataIn);

        $this->types = $types ?? [];
    }

    public function getAllTypes(): array
    {
        return $this->types;
    }

    private function getType($property)
    {
        if (!array_key_exists($property, $this->types))
            return null;

	    if (empty($this->types[$property]))
 	        return null;

	    if (is_array($this->types[$property]))
	    {
		    if (!array_key_exists("type", $this->types[$property]))
	  	        return null;

		    $typeParts = [];
		    if (preg_match("/^([A-Z]+)/", $this->types[$property]["type"], $typeParts) && !empty($typeParts))
               	   return $typeParts[1];
            return $this->types[$property]["type"];
	    }

	    if (is_string($this->types[$property]))
	        return $this->types[$property];

        return null;
    }

    public function __get($property)
    {
        if (isset($this->camps[$property]))
        {
            $type = $this->getType($property);
            if ($type === "INTEGER")
                return (int) $this->camps[$property];
            if ($type === "NUMERIC")
                return (double) $this->camps[$property];
            if ($type === "DECIMAL")
                return (float) $this->camps[$property];

            /*else if ($type === "DATE")
                return DateTime::createFromFormat("d/m/Y", $this->camps[$property]);
            else if ($type === "TIMESTAMP")
                return DateTime::createFromFormat("d.m.Y H:i:s", $this->camps[$property]);*/
            return $this->camps[$property];
        }

        return null;
    }

    public function __set($name, $value)
    {
        $this->camps[$name] = $value;
    }

    public function __isset($name)
    {
        return (isset($this->camps[$name]));
    }

    public function __unset($name)
    {
        unset($this->camps[$name]);
    }
}
