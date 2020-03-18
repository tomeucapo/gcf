<?php

use Laminas\Json\Json;

trait modulBaseCRUD
{
    /**
     * @var taulaBD
     */
    private $tbl;

    /**
     * @param $data
     * @return bool
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    public function Nou($data)
    {
        $this->tbl->camps = Json::decode(utf8_encode($data), Json::TYPE_ARRAY);
        return $this->tbl->Nou();
    }

    /**
     * @param $id
     * @param $data
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    public function Modifica($id, $data)
    {
        $this->tbl->camps = Json::decode(utf8_encode($data), Json::TYPE_ARRAY);
        $this->tbl->Modifica($id);
    }

    /**
     * @param $id
     * @param $data
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    public function Modificar($id, $data)
    {
        $this->Modifica($id, $data);
    }

    /**
     * @param $id
     * @throws errorQuerySQL
     * @throws noPrimaryKey
     */
    public function Borra($id)
    {
        $this->tbl->Borra($id);
    }

    /**
     * @param $id
     * @throws errorQuerySQL
     * @throws noPrimaryKey
     */
    public function Borrar($id)
    {
        $this->Borra($id);
    }
}