<?php

namespace gcf\web\controllers;

use gcf\database\drivers\errorQuerySQL;;
use gcf\database\errorDriverDB;
use gcf\database\models\noDataFound;
use gcf\database\models\noPrimaryKey;

trait modulBaseCRUD
{
    /**
     * @param array $data
     * @return bool
     * @throws errorDriverDB
     * @throws errorQuerySQL
     * @throws noDataFound
     * @throws noPrimaryKey
     */

    #[AcceptVerbs(WSMethod::POST)]
    public function Nou(array $data)
    {
        $this->tbl->camps = $data;
        return $this->tbl->Nou();
    }

    /**
     * @param $id
     * @param $data
     * @throws errorQuerySQL
     * @throws noDataFound
     * @throws noPrimaryKey
     */
    #[AcceptVerbs(WSMethod::PUT)]
    public function Modifica($id, array $data)
    {
        $this->tbl->camps = $data;
        $this->tbl->Modifica($id);
    }

    /**
     * @param $id
     * @param $data
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    #[AcceptVerbs(WSMethod::PUT)]
    public function Modificar($id, array $data)
    {
        $this->Modifica($id, $data);
    }

    /**
     * @param $id
     * @throws errorQuerySQL
     * @throws noPrimaryKey
     */

    #[AcceptVerbs(WSMethod::DELETE)]
    public function Borra($id)
    {
        $this->tbl->Borra($id);
    }

    /**
     * @param $id
     * @throws errorQuerySQL
     * @throws noPrimaryKey
     */

    #[AcceptVerbs(WSMethod::DELETE)]
    public function Borrar($id)
    {
        $this->Borra($id);
    }
}