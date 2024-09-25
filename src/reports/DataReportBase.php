<?php

namespace gcf\reports;

use gcf\cache\cachePlugin;
use gcf\database\DatabaseConnector;
use gcf\Environment;
use gcf\web\ws\JSONEncodingError;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use stdClass;

abstract class DataReportBase implements DataReport
{
    const XML_OUT = 1;
    const JSON_OUT = 2;
    const XLS_OUT = 3;
    const PDF_OUT = 4;

    private static array $cellStylesDetails = [
        'font' => [
            'name' => 'Courier New',
            'size' => 9
        ],
        'borders' => [
            'bottom' => [
                'style' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ],
        'fill' => ['fillType' => Fill::FILL_SOLID,
            "color" => ['rgb' => 'FFFFFF']]
    ];

        public stdClass $filtres;
        
        protected string $name;
        private string $title;
        private array $subtitles = [];

        private array $footerText;

        private ?cachePlugin $cache = null;

        protected DatabaseConnector $db;

        protected ReportHeaders $header;

        protected array $data;

        protected array $metaInfo=[];
        protected int $typeOut;

        private string $orientation;
        private int $pageSize;

        public function __construct(DatabaseConnector $db, $name, cachePlugin $cache=null, $type = self::JSON_OUT)
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

        public function SetPageLayout($size, $orientation) : void
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

        public function SetHeaders(ReportHeaders $header) : void
        {
               $this->header = $header;
        }

    /**
     * @throws \Exception
     */
    protected function DefineColumns(array $definitions) : void
        {
            $headers = new ReportHeaders();

            foreach ($definitions as $columna)
            {
                if (array_key_exists("type", $columna))
                    $column = new ReportColumn($columna["type"]);
                else $column = new ReportColumn();

                if (!array_key_exists("key", $columna))
                    throw new \Exception("You need specify key of column!");

                if (!array_key_exists("label", $columna))
                    throw new \Exception("You need specify label of column!");

                $column->key = $columna["key"];
                $column->label = $columna["label"];

                if (array_key_exists("width", $columna))
                    $column->width = $columna["width"];

                if (array_key_exists("formatter", $columna))
                    $column->formatter = $columna["formatter"];

                if (array_key_exists("filter", $columna))
                    $column->filter = $columna["filter"];

                if (array_key_exists("className", $columna))
                    $column->className = $columna["className"];

                if (array_key_exists("sort", $columna))
                    $column->sortable = $columna["sortable"];

                if (array_key_exists("children", $columna))
                    $columna->children = $columna["children"];

                $headers->addHeader($column);
            }

            $this->SetHeaders($headers);
        }

        public function GetHeaders(): ReportHeaders
        {
               return $this->header;
        }
        
        public function SetTitle($title) : void
        {
               $this->title = $title;
        }

        public function AddFooter($footer) : void
        {
               $this->footerText[] = $footer;
        }

        public function AddSubtitle($subTitle) : void
        {
               $this->subtitles[] = $subTitle;
        }
        
        protected function addData(array $data) : void
        {
               // Pasam a UTF-8 per que les funcions json_encode necessita dades en format UTF-8
           
               array_walk_recursive($data, function (&$item) { if (is_string($item)) $item = mb_encode_numericentity($item, array (0x80, 0xffff, 0, 0xffff), 'UTF-8'); });

               $this->data[] = $data;
        }
        
        abstract protected function Execute() : void;
        
        public function contentType() : string
        {
            return match ($this->typeOut) {
                self::JSON_OUT => "application/json",
                self::XLS_OUT => "application/vnd.ms-excel",
                self::PDF_OUT => "application/pdf",
                self::XML_OUT => "text/xml",
                default => "text/plain",
            };
        }
        
        protected function setType($type) : void
        {
                  $this->typeOut = $type;
        }

    /**
     * @return Spreadsheet
     * @throws Exception
     */
        private function XSLEncode(): Spreadsheet
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
				  if (!empty($this->title))
				  {
					$sheet->mergeCells("A".$row.":"."E".$row);
					$sheet->setCellValue("A".$row++, $this->title);
					$sheet->getStyle('A1')->getFont()->setSize(20);
					$sheet->getStyle('A1')->getFont()->setBold(true);
					$sheet->getStyle('A1')->applyFromArray($cellStyles);
				  }	
				  
				  if (!empty($this->subtitles))
				  {
					foreach ($this->subtitles as $sub)
					{
                           $sheet->setCellValue("A".$row, $sub);
                           $sheet->mergeCells("A".$row.":"."E".$row);
                           $sheet->getStyle("A".$row)->applyFromArray($cellStyles);
                           $row++;
					}
					$row++;
				  }
                  $colDefs = $this->header->getDefinitions();

                  // Afegim les etiquetes de les columnes
                  foreach ($colDefs as $headerDef)
                  {
                           $def = $headerDef->props;
                           $sheet->setCellValue([$col, $row], strip_tags($def["label"]));
			   
			   // TODO: S'ha de canviar el A per l'equivalencia de $col
			   $sheet->getStyle("A".$row)->applyFromArray($cellStylesHeaders);
			
			   $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
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

                                  $sheet->getStyle([$col, $row])->applyFromArray(self::$cellStylesDetails);
                                  if ($colDefs[$col-1]->type === ReportColumnType::NUMBER_FORMAT)
                                  {
                                      $sheet->getStyle([$col, $row])->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
                                      $sheet->setCellValue([$col, $row], str_replace(",",".",$reg[$field]));
                                  } else {
                                      if (array_key_exists("formatter",$colDefs[$col-1]->props) && $colDefs[$col-1]->props["formatter"] === "currencyEur")
                                          $sheet->getStyle([$col, $row])->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
                                      else
                                          if (array_key_exists("className",$colDefs[$col-1]->props) && $colDefs[$col-1]->props["className"] === "align-right")
                                              $sheet->getStyle([$col, $row])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                          else
                                              $sheet->getStyle([$col, $row])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                                      $sheet->setCellValue([$col, $row], array_key_exists($field, $reg) ? $reg[$field] : "");
                                  }

                                  if ($row%2)
                                      $sheet->getStyle([$col, $row])->getFill()->getStartColor()->setRGB("F1F1F1");
                                  else $sheet->getStyle([$col, $row])->getFill()->getStartColor()->setRGB("E1E1E1");

                                  $col++;
                           }
                           $row++;
                  }

                  $col = 1;$row++;$rowIni = $row;
                  foreach ($this->footerText as $txt)
                  {
                      $sheet->setCellValue([$col, $row], $txt);

                      //$sheet->mergeCells("A".$row.":"."E".$row);

                      $sheet->mergeCells([$col, $row, $col+5, $row]);
                      $sheet->getStyle([$col, $row])->applyFromArray($cellStyles);
                      $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                      if ($row%5 === 0) {
                          $col += 6;
                          $row = $rowIni+1;
                      } else
                        $row++;
                  }

                  return $objExcel;
        }
        
        private function doExecute(): stdClass
        {
                $this->Execute();                                      
                       
                $reportData = new stdClass();
                $reportData->header   = $this->header;
                $reportData->data     = $this->data;
                $reportData->metaInfo = $this->metaInfo;
                $reportData->subtitles= $this->subtitles;
                $reportData->title    = $this->title;
                
                return $reportData;
        }

    /**
     * @throws JSONEncodingError
     * @throws Exception
     * @throws \Exception
     */
        public function GetData() : void
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
                      throw new JSONEncodingError($jsonEncError);
			      
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
                  $configParms = Environment::getInstance()->config;
                  $objWriter = new Mpdf($this->XSLEncode());
                  $objWriter->setTempDir($configParms->paths->path->temp);

                  header('Content-Disposition: inline;filename="'.$this->name.'.pdf"');
                  header('Cache-Control: max-age=0');

                  $objWriter->save('php://output');
               }
        }        
}
