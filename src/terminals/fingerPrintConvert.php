<?php
/**
 * fingerPrintConvert
 * Classe que permet convertir una empremta en format CP5000 a una de CP6000 i al inrreves
 *
 * Tomeu Capo Capo 2013 (C)
 *
 */

namespace gcf\terminals;

use DOMDocument;

class fingerPrintConvert
{
        private $header, $tmpls;

        public function __construct()
        {
		   $this->tmpls = array();
		   $this->header = array();
        }

    /**
     * @param $dataIn
     * @return array
     * @throws dataTemplateError
     * @throws numTemplatesError
     * @throws personalCodeError
     */
        public function toCP6000($dataIn)
        {
                $fingerData = $dataIn["data"];
                $this->personalCode = $dataIn["id"];

                if (strlen($fingerData)<10)
                            throw new dataTemplateError("TEMPLATE ERROR: Longitud de dades incorrecte");

                // Llegim la capçalera ( Cada dada ve amb representaci� little-endian ).

                $id = (int)hexdec(substr($fingerData,0,4));			// Identificador personal  
                $numTmpls = (int)hexdec(substr($fingerData,4,2));	// Nombre d'empremtes
                $lenData = (int)hexdec(substr($fingerData,6,4));		// Longitud totat dels blocs de dades

                // Convertim les dades en valors

                $personalCode = (($id & 0x00ff) << 8) | (($id & 0xff00) >> 8);
                $lenData = ((($lenData & 0x00ff) << 8) | (($lenData & 0xff00) >> 8)) << 1;

                $this->header = array("id" => $personalCode, "len" => $lenData, "numTmpls" => $numTmpls);

                if ($personalCode != (int) $dataIn["id"])
                    throw new personalCodeError("El codi {$dataIn["id"]} de l'empremta no coincideix amb el del template $personalCode: $fingerData");

                if ($numTmpls != (int) $dataIn["nfingerprints"])
                    throw new numTemplatesError("El nombre d'empremtes no coincideix amb els del template"); 

                if ($numTmpls == 0) 
                   return $this->tmpls;

                // Obtenim la longitud del primer bloc de dades		    

                $len1 = (int)hexdec(substr($fingerData, 10, 4));
                $size1 = ((($len1 & 0x00ff) << 8) | (($len1 & 0xff00) >> 8)) << 1;

                $this->tmpls[] = substr($fingerData, 14, $size1);

                // Si hi ha dos blocs, agafam el segon bloc de dades

                if ($numTmpls == 2)
                {
                    $len2 = (int)hexdec(substr($fingerData, $size1+14, 4));
                    $size2 = ((($len2 & 0x00ff) << 8) | (($len2 & 0xff00) >> 8)) << 1;

                    $this->tmpls[] = substr($fingerData, $size1+18, $size2);
                }

                return $this->tmpls;
        }

        public function toCP5000($id, $nfingerPrints, $dataIn)
        {
                $id = (int)$id;
                $nfingerPrints = (int) $nfingerPrints;

                $idLE = (($id & 0x00ff) << 8) | (($id & 0xff00) >> 8);

                $dataBlocks = "";
                foreach($dataIn as $tmpl)
                {   
                        $tmplLen = strlen($tmpl) >> 1;
                        $tmplLenLE = (($tmplLen & 0x00ff) << 8) | (($tmplLen & 0xff00) >> 8);
                        $dataBlocks .= sprintf("%04X%s", $tmplLenLE, $tmpl);
                }
                
                $lenData = strlen($dataBlocks) >> 1;
                $lenDataLE = (($lenData & 0x00ff) << 8) | (($lenData & 0xff00) >> 8);
                $header = sprintf("%04X%02X%04X", $idLE, $nfingerPrints, $lenDataLE);

                return array("id"            => $id,
                             "nfingerprints" => $nfingerPrints,
                             "data"          => $header.$dataBlocks);
        }

        public function getXMLNode($root, DOMDocument $domIn)
        {
                if (empty($this->tmpls))
                    return;

                $root->appendChild($domIn->createElement("id", $this->personalCode));
                $xmlTmpls = $root->appendChild($domIn->createElement("templates"));

                foreach($this->tmpls as $tmpl)
                        $xmlTmpls->appendChild($domIn->createElement("i", $tmpl));
        }

        public function LastHeader() : array
        {
            return $this->header;
        }
}