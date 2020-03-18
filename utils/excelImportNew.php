<?php

namespace {
    require_once 'PHPExcel/Classes/PHPExcel/IOFactory.php';
}

namespace Gcf\Utils\Importers
{

  class DataIterator implements \Iterator
  {
      /**
       * @var excelImportNew
       */
      private $data;

      public function __construct(excelImportNew $data)
      {
            $this->data = $data;
      }

      /**
       * Return the current element
       * @link http://php.net/manual/en/iterator.current.php
       * @return mixed Can return any type.
       * @since 5.0.0
       */
      public function current()
      {
          return $this->data->getRow();
      }

      /**
       * Move forward to next element
       * @link http://php.net/manual/en/iterator.next.php
       * @return void Any returned value is ignored.
       * @since 5.0.0
       */
      public function next()
      {
          $this->data->skip();
      }

      /**
       * Return the key of the current element
       * @link http://php.net/manual/en/iterator.key.php
       * @return mixed scalar on success, or null on failure.
       * @since 5.0.0
       */
      public function key()
      {
          return $this->data->rowNum();
      }

      /**
       * Checks if current position is valid
       * @link http://php.net/manual/en/iterator.valid.php
       * @return boolean The return value will be casted to boolean and then evaluated.
       * Returns true on success or false on failure.
       * @since 5.0.0
       */
      public function valid()
      {
          return !$this->data->eof();
      }

      /**
       * Rewind the Iterator to the first element
       * @link http://php.net/manual/en/iterator.rewind.php
       * @return void Any returned value is ignored.
       * @since 5.0.0
       */
      public function rewind()
      {
          $this->data->goTop();
      }
  }

    /**
     * Class excelImportNew
     * @package Gcf\Utils\Importers
     */
  abstract class excelImportNew
  {
      protected $iniFila, $iniCol, $pRow;
      private $sheet;
      private $highestRow, $highestColumn;
      
      public function __construct($nomFitxer, $sheetNum=0)
      {             
               if (!file_exists($nomFitxer))
                   throw new \Exception("$nomFitxer file not found!");
               
               $inputFileType = \PHPExcel_IOFactory::identify($nomFitxer);
               $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
               $objPHPExcel = $objReader->load($nomFitxer);

               $this->sheet = $objPHPExcel->getSheet($sheetNum);
               $this->highestRow = $this->sheet->getHighestRow();
               $this->highestColumn = $this->sheet->getHighestColumn();
               $this->iniCol='A';
      }
      
      public function getNumCols()
      {
              return $this->highestColumn;
      }
      
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
      public function getRow($col=null, $row=null)
      {
               $colIni = ($col !== null) ? $col : $this->iniCol;
               $row = ($row !== null) ? $row : $this->pRow;

               $rowData = $this->sheet->rangeToArray($colIni . $row . ':' . $this->highestColumn . $row,
    NULL, TRUE, FALSE);
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
}