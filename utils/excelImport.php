<?php

namespace {
    require_once 'reader.php';
}

namespace Gcf\Utils\Importers
{
  abstract class excelImport
  {
      protected $iniFila, $iniCol, $pRow;
      private $data;
      
      public function __construct($nomFitxer)
      {             
               if (file_exists($nomFitxer))
               {
                   $this->data = new \Spreadsheet_Excel_Reader();
	               $this->data->setOutputEncoding('CP1251');
	               $this->data->read($nomFitxer);
               } else
                    die("El fitxer ".$nomFitxer." no existeix!");
      }
      
      public function getNumCols()
      {
              return $this->data->sheets[0]['numCols'];
      }
      
      // Ens torna la fila actual
      
      public function getRow()
      {
               $fila = array();
               echo count($this->data->sheets[0]['cells'][$this->pRow]);
        	   for ($j = $this->iniCol; $j <= $this->data->sheets[0]['numCols']; $j++) {
                    echo "$j\n";
                    $fila[] = $this->data->sheets[0]['cells'][$this->pRow][$j];
               }
                 
               return $fila;
      }

      // Ens diu si hem arribat al final del llistat
      
      public function eof()
      {
               $row = $this->pRow;
               return(!(($row <= $this->data->sheets[0]['numRows']) && ($this->data->sheets[0]['cells'][$row][2]!='')));
      }
     
      // Salta una fila
      
      public function skip()
      {
               if ($this->pRow <= $this->data->sheets[0]['numRows'])
                   $this->pRow++;
      }

      public function goTop()
      {
               $this->pRow = $this->iniFila;
      }
}
}
?>
