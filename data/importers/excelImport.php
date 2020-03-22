<?php

namespace gcf\data\importers;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class excelImport
 * @package Gcf\Data\Importers
 */
abstract class excelImport
{
    protected $iniFila, $iniCol, $pRow;
    private $sheet;
    private $highestRow, $highestColumn;

    /**
     * excelImportNew constructor.
     * @param $nomFitxer
     * @param int $sheetNum
     * @throws \Exception
     */
    public function __construct($nomFitxer, $sheetNum = 0)
    {
        if (!file_exists($nomFitxer))
            throw new \Exception("$nomFitxer file not found!");

        $objPHPExcel = IOFactory::load($nomFitxer);

        $this->sheet = $objPHPExcel->getSheet($sheetNum);
        $this->highestRow = $this->sheet->getHighestRow();
        $this->highestColumn = $this->sheet->getHighestColumn();
        $this->iniCol = 'A';
    }

    /**
     * @return string
     */
    public function getNumCols()
    {
        return $this->highestColumn;
    }

    /**
     * @param $cellId
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function getCell($cellId)
    {
        return $this->sheet->getCell($cellId)->getValue();
    }

    /**
     * Ens torna la fila actual o bé la seleccionada
     * @param null $col
     * @param null $row
     * @return mixed
     */
    public function getRow($col = null, $row = null)
    {
        $colIni = ($col !== null) ? $col : $this->iniCol;
        $row = ($row !== null) ? $row : $this->pRow;

        $rowData = $this->sheet->rangeToArray($colIni . $row . ':' . $this->highestColumn . $row, NULL, TRUE, FALSE);
        return $rowData[0];
    }

    /**
     * Ens diu si hem arribat al final del llistat
     */
    public function eof()
    {
        return (!($this->pRow <= $this->highestRow));
    }

    /**
     * Salta a la següent fila
     */
    public function skip()
    {
        if ($this->pRow <= $this->highestRow)
            $this->pRow++;
    }

    /**
     * Es posa a la primera fila de dades
     */
    public function goTop()
    {
        $this->pRow = $this->iniFila;
    }

    /**
     * Ens torna la fila actual
     */
    public function rowNum()
    {
        return $this->pRow;
    }

    public function getIterator()
    {
        return new DataIterator($this);
    }
}