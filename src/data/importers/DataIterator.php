<?php

namespace gcf\data\importers;

class DataIterator implements \Iterator
{
    private excelImport $data;

    public function __construct(excelImport $data)
    {
        $this->data = $data;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return int Can return any type.
     * @since 5.0.0
     */
    public function current() : int
    {
        return $this->data->getRow();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next() : void
    {
        $this->data->skip();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return int scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key() : int
    {
        return $this->data->rowNum();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be cast to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid() : bool
    {
        return !$this->data->eof();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind() : void
    {
        $this->data->goTop();
    }
}