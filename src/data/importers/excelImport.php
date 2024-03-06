<?php

namespace gcf\data\importers;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class excelImport
 * @package Gcf\Data\Importers
 */
abstract class excelImport
{
    protected string $iniCol = 'A';
    protected int $iniFila, $pRow;

    private Worksheet $sheet;

    private int $highestRow;
    private string $highestColumn;

    /**
     * excelImportNew constructor.
     * @param string $nomFitxer
     * @param int $sheetNum
     * @throws \Exception
     */
    public function __construct(string $nomFitxer, int $sheetNum = 0)
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
    public function GetHighestCol(): string
    {
        return $this->highestColumn;
    }

    /**
     * @param $cellId
     * @return mixed
     * @throws Exception
     */
    public function getCell($cellId) : mixed
    {
        return $this->sheet->getCell($cellId)->getValue();
    }

    /**
     * Ens torna la fila actual o bé la seleccionada
     * @param ?string $col
     * @param ?int $row
     * @return mixed
     */
    public function getRow(?string $col = null, ?int $row = null) : mixed
    {
        $colIni = ($col !== null) ? $col : $this->iniCol;
        $row = ($row !== null) ? $row : $this->pRow;

        $rowData = $this->sheet->rangeToArray($colIni . $row . ':' . $this->highestColumn . $row, NULL, TRUE, FALSE);
        return $rowData[0];
    }

    /**
     * Ens diu si hem arribat al final del llistat
     */
    public function eof(): bool
    {
        return (!($this->pRow <= $this->highestRow));
    }

    /**
     * Salta a la següent fila
     */
    public function skip() : void
    {
        if ($this->pRow <= $this->highestRow)
            $this->pRow++;
    }

    /**
     * Es posa a la primera fila de dades
     */
    public function goTop() : void
    {
        $this->pRow = $this->iniFila;
    }

    /**
     * Ens torna la fila actual
     */
    public function rowNum() : int
    {
        return $this->pRow;
    }

    public function getIterator(): DataIterator
    {
        return new DataIterator($this);
    }
}