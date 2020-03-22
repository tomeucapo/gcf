<?php

namespace gcf\reports;

include "DataReport.php";
include "ReportHeaders.php";
include "ReportColumn.php";
include "web/ws/wsBase.php";

use gcf\cache\cachePlugin;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

abstract class DataReportBase implements DataReport
{     
        const XML_OUT = 1;
        const JSON_OUT = 2;
        const XLS_OUT = 3;
        const PDF_OUT = 4;


        private static $cellStylesDetails = [
	        'font' => [
		            'name' => 'Courier New',
		                'size' => 9
		            ],
	            'borders' => [
	            'bottom' => [
	                'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
            ]
        ],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                   "color" => ['rgb' => 'FFFFFF']]
	       ];

        public $filtres;
        
        private $name;
        private $title, $subtitles;

    /**
     * @var array
     */
        private $footerText;

    /**
     * @var cachePlugin
     */
        private $cache;

        /**
         *    @var \base_dades
         */
        protected $db;

        /**
        * @var ReportHeaders
        */
        protected $header;

        protected $data, $typeOut, $metaInfo;

        /**
         * @var \ConfiguratorBase
         */
        protected $config;

        /**
         * @var string
         */
        private $orientation;

        /**
         * @var int
         */
        private $pageSize;

        public function __construct(\base_dades $db, $name, cachePlugin $cache=null, $type = self::JSON_OUT)
        {
               $this->title = "";
               $this->name = $name;
               $this->db = $db;  
               $this->data = array();
               $this->typeOut = $type;
               $this->orientation = PageSetup::ORIENTATION_LANDSCAPE;
               $this->pageSize = PageSetup::PAPERSIZE_A4;

               $this->footerText = [];

               if ($cache)
                   $this->cache = $cache;
        }

        public function SetPageLayout($size, $orientation)
        {
                if ($size==="A3")
                    $this->pageSize =  PageSetup::PAPERSIZE_A3;
                if ($size==="A4")
                    $this->pageSize =  PageSetup::PAPERSIZE_A4;

                if ($orientation==="LANDSCAPE")
                    $this->orientation =  PageSetup::ORIENTATION_LANDSCAPE;
                if ($orientation==="PORTRAIT")
                    $this->orientation =  PageSetup::ORIENTATION_PORTRAIT;
        }

        public function SetHeaders(ReportHeaders $header)
        {
               $this->header = $header;
        }
        
        public function GetHeaders() 
        {
               return $this->header;
        }
        
        public function SetTitle($title)
        {
               $this->title = $title;
        }

        public function AddFooter($footer)
        {
               $this->footerText[] = $footer;
        }

        public function AddSubtitle($subTitle)
        {
               $this->subtitles[] = $subTitle;
        }
        
        protected function addData($data)
        {
               // Pasam a UTF-8 per que les funcions json_encode necessita dades en format UTF-8
           
               array_walk_recursive($data, function (&$item, $key) { if (is_string($item)) $item = mb_encode_numericentity($item, array (0x80, 0xffff, 0, 0xffff), 'UTF-8'); });

               $this->data[] = $data;
        }
        
        abstract protected function Execute();
        
        public function contentType()
        {
                switch($this->typeOut) {
                    case self::JSON_OUT:
                        $contentType = "application/json";
                        break;
                    case self::XLS_OUT:
                        $contentType = "application/vnd.ms-excel";
                        break;
                    case self::PDF_OUT:
                        $contentType = "application/pdf";
                        break;
                    case self::XML_OUT:
                        $contentType = "text/xml";
                        break;
                        default:
                        $contentType = "text/plain";
                }
                return $contentType;
        }
        
        protected function setType($type)
        {
                  $this->typeOut = $type;
        }

    /**
     * @return Spreadsheet
     * @throws Exception
     */
        private function XSLEncode()
        {
                  $objExcel = new Spreadsheet();
                  $objExcel->getProperties()->setCreator("Personal by Phi Consultors (C)")
                                            ->setTitle($this->name);

                  $cellStyles = array(
                         'borders' => array(
                             'allborders' => array(
                                 'style' => Border::BORDER_THIN,
                                 'color' => array('rgb' => 'FFFFFF')
                              )
                         )
                  );

                   $cellStylesHeaders = [
                        'borders' => [
                            'allborders' => [
                                'style' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'bottom' => [
                                'style' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ],
                        ],
                    ];


                  $objExcel->getActiveSheet()
                           ->setShowGridlines(false);

                  $sheet = $objExcel->setActiveSheetIndex(0);

                  // Configuraci� de les pagines
                  $sheet->getPageSetup()->setOrientation($this->orientation);
                  $sheet->getPageSetup()->setPaperSize($this->pageSize);
                  $sheet->getPageSetup()->setFitToWidth(1);
                  $sheet->getPageSetup()->setFitToHeight(0);

                  $sheet->getHeaderFooter()->setEvenHeader('&C&H'. $this->title);
                  $sheet->getHeaderFooter()->setEvenFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPagina &P of &N (&D &T)');

                  $col = 1; $row = 1;
                  $sheet->mergeCellsByColumnAndRow($col, $row, $col+5, $row);
                  $sheet->setCellValueByColumnAndRow($col, $row++, $this->title);                  
                  $sheet->getStyle('A1')->getFont()->setSize(20);
                  $sheet->getStyle('A1')->getFont()->setBold(true);
                  $sheet->getStyle('A1')->applyFromArray($cellStyles);

                  foreach ($this->subtitles as $sub)
                  {
                           $sheet->setCellValueByColumnAndRow($col, $row, $sub);
                           $sheet->mergeCellsByColumnAndRow($col, $row, $col+5, $row);
                           $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($cellStyles);
                           $row++;
                  }


                  $row++;

                  $colTypes = [];
                  // Afegim les etiquetes de les columnes
                  foreach ($this->header->getDefinitions() as $headerDef)
                  {
                           $def = $headerDef->props;
                           $sheet->setCellValueByColumnAndRow($col, $row, strip_tags($def["label"]));
                           $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($cellStylesHeaders);
                           //if ($def["filter"]) {
                           //    $sheet->setAutoFilterByColumnAndRow($col, $row);
                           //}
                           $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                           $colTypes[] = $headerDef->type;
                           $col++;
                  }


                  // TODO: Falta comprovar si es necess�ri filtrar o no depenent del tipus de columna
                  //$sheet->setAutoFilter("A$row:C$row");

                  // Totes les dades de cada fila
                  $row++;                 
                  foreach ($this->data as $reg)
                  {               
                           $col = 1;
                           foreach($this->header->getFields() as $field)
                           {
                                  // TODO: Review this str_replace, is dirty trick!

                                  $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray(self::$cellStylesDetails);
                                  if ($colTypes[$col-1] === ReportColumn::NUMBER_FORMAT)
                                  {
                                      $sheet->getStyleByColumnAndRow($col, $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                                      $sheet->setCellValueByColumnAndRow($col, $row, str_replace(",",".",$reg[$field]));
                                  } else {
                                      $sheet->setCellValueByColumnAndRow($col, $row, $reg[$field]);
                                  }

                                  if ($row%2)
                                      $sheet->getStyleByColumnAndRow($col, $row)->getFill()->getStartColor()->setRGB("F1F1F1");
                                  else $sheet->getStyleByColumnAndRow($col, $row)->getFill()->getStartColor()->setRGB("E1E1E1");

                                  $col++;
                           }
                           $row++;
                  }

                  $col = 1;$row++;$rowIni = $row;
                  foreach ($this->footerText as $txt)
                  {
                      $sheet->setCellValueByColumnAndRow($col, $row, $txt);
                      $sheet->mergeCellsByColumnAndRow($col, $row, $col+5, $row);
                      $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($cellStyles);
                      $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                      if ($row%5 === 0) {
                          $col += 6;
                          $row = $rowIni+1;
                      } else
                        $row++;
                  }

                  return $objExcel;
        }
        
        private function doExecute()
        {
                $this->Execute();                                      
                       
                $reportData = new \stdClass();
                $reportData->header   = $this->header;
                $reportData->data     = $this->data;
                $reportData->metaInfo = $this->metaInfo;
                $reportData->subtitles= $this->subtitles;
                $reportData->title    = $this->title;
                
                return $reportData;
        }

    /**
     * @throws \JSONEncodingError
     * @throws Exception
     */
    public function GetData()
        {
               if ($this->cache)
               {     
                   $keyDataReport = $this->name.":".sha1(serialize($this->filtres));
                   $reportData = $this->cache->get($keyDataReport);
                   
                   if (!$reportData)
                   {
                       $reportData = $this->doExecute();
                       $this->cache->set($keyDataReport, $reportData);                   
                   } else {
                       $this->data = $reportData->data;
                       $this->header = $reportData->header;
                       $this->metaInfo = $reportData->metaInfo;
                       $this->subtitles = $reportData->subtitles;
                       $this->title = $reportData->title;
                   }
               } else   
                   $this->doExecute();
             
               header('Content-Type: '.$this->contentType());
               
               if ($this->typeOut == self::JSON_OUT)
               {
                  $cols = [];
                  foreach ($this->header->getDefinitions() as $def)
                      $cols[] = $def->props;

                  $resultSet = ["ResultSet" => ["Columns" => $cols,
                                                "Result"  => $this->data,
                                                "Meta"    => $this->metaInfo]];

                  echo json_encode($resultSet);
     
                  $jsonEncError = json_last_error();
                  if ($jsonEncError)
                      throw new \JSONEncodingError($jsonEncError);
			      
                  return;
               }
               
               if ($this->typeOut == self::XLS_OUT)
               {                   
                   header('Content-Disposition: inline;filename="'.$this->name.'.xlsx"');
                   header('Cache-Control: max-age=0');

                   $objWriter = new Xlsx($this->XSLEncode());
                   $objWriter->save('php://output');
                   return;
               }

               if ($this->typeOut == self::PDF_OUT)
               {
                  $configParms =  $this->config->getConfig();
                  $objWriter = new Mpdf($this->XSLEncode());
                  $objWriter->setTempDir($configParms->paths->path->temp);

                  header('Content-Disposition: inline;filename="'.$this->name.'.pdf"');
                  header('Cache-Control: max-age=0');

                  $objWriter->save('php://output');
               }
        }        
}
