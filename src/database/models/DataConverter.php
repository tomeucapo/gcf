<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 6/19/2018
 * Time: 9:06 PM
 */

namespace gcf\database\models;

use DateTime;

class DataConverter
{
    // TODO: A futur aquesta constant ha de desapareixer per agafar els parametres de configuració de BD
    const DEFAULT_DB_DATE_FMT = 'd.m.Y';

    private string $tableName;
    private bool $swapDate;
    private ?string $generalWhere;
    public array $dataFields;

    public function __construct(DataMapper $table, bool $swapDate = true)
    {
            $this->tableName = $table->nomTaula;
            $this->generalWhere = null;
            $this->dataFields = [];

            if (!$swapDate)
                $this->swapDate = ($table->getConnection()->drv === "firebird");
            else $this->swapDate = $swapDate;
    }

    /**
     * Read XML data and create dictionary field => value
     * @param $dades_xml
     * @return array
     */
    public static function ReadDataXML($dades_xml) : array
    {
	    //$recodifica = (mb_detect_encoding($dades_xml) === 'UTF-8');
        $dades_xml = str_replace('\\"','"', $dades_xml);
        $nodeArray = [];

        $xmlDom = new \DOMDocument("1.0", "utf-8");
        $xmlDom->loadXML($dades_xml);

        $root = $xmlDom->documentElement;
        foreach ($root->childNodes as $node)
        {
            // TODO: Improve this!
            if ($node->nodeName==='#text')
                continue;

	        $nodeArray[strtoupper($node->nodeName)] = $node->nodeValue;

            //$nodeArray[strtoupper($node->nodeName)] = ($recodifica) ?
            //    iconv("UTF-8", "ISO-8859-15", $node->nodeValue) : $node->nodeValue;
        }

        return $nodeArray;
    }

    /**
     * Adding additional where condition on final generated sentence
     * @param string|null $cond
     */
    public function Where(?string $cond) : void
    {
            $this->generalWhere = $cond;
    }

    /**
     * Convert XML data to SQL sentence
     * @param ConverterType $type
     * @param $dataXML
     * @return string
     * @throws \Exception
     */
    public function XMLToSQL(ConverterType $type, $dataXML) : string
    {
        $this->dataFields = self::ReadDataXML($dataXML);
        return $this->ArrayToSQL($type, $this->dataFields);
    }

    /**
     * A partir dels camps tornats de la funcio tracta_xml generam la sentencia
     * SQL de la BD.
     *
     * @param ConverterType $tipus Tipus d'operació a generar
     * @param array $camps Llista de camps i els seus valors
     * @return string Sentencia SQL generada
     * @throws \Exception
     */
    public function ArrayToSQL(ConverterType $tipus, array $camps): string
    {
        $valorsCamps = [];
        $s_camps_db = [];

        foreach($camps as $nom_camp => $valor)
        {
            $valor=str_replace("'", "''", $valor);
            $valor=str_replace("\'", "''", $valor);

            if (empty($nom_camp))
                continue;

            $s_camps_db[] = $nom_camp;

            if (trim($valor)!=='')          // Use this comparsion instead empty because 0 values may confuse empty function
            {
                if($this->swapDate)
                {
                    if(preg_match("/^([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4})$/", $valor,$data))
                    {
                        $campData = DateTime::createFromFormat("d/m/Y", $data[1]);
                        if ($campData === false)
                            throw new \Exception("$data[1] Data incorrecte");

                        $valorDataFmt = $campData->format(self::DEFAULT_DB_DATE_FMT);
                        $valor = "'$valorDataFmt'";
                    } else {
                        if (preg_match("/^([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}).([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})$/", $valor, $dataHora))
                        {
                            $campData = DateTime::createFromFormat("d/m/Y", $dataHora[1]);
                            if ($campData === false)
                                throw new \Exception("$dataHora[1] Data incorrecte");

                            $valorDataFmt = $campData->format(self::DEFAULT_DB_DATE_FMT);
			                $valor = "'$valorDataFmt $dataHora[2]'";
                        } else {
                            if($valor!=='?')                // Si un camp te per valor un? és que li passam un blob
                                $valor = "'".$valor."'";
                        }
                    }
                }
                else $valor = "'".$valor."'";
            }
            else
                $valor = "null";

            if($tipus == ConverterType::SQLInsert) {
                $valorsCamps[] = $valor;
            } else if($tipus == ConverterType::SQLUpdate) {
                $valorsCamps[] = "$nom_camp = ".$valor;
            }
        }

        $query = '';
        $s_camps_valors = implode(",",$valorsCamps);

        if ($tipus === ConverterType::SQLInsert)
        {
            $query = "insert into $this->tableName (".implode(",", $s_camps_db).") values ($s_camps_valors);";
        } else
            if ($tipus === ConverterType::SQLUpdate)
            {
                $whereExp = "";
                if(!empty($this->generalWhere))
                   $whereExp = "where $this->generalWhere";
                $query = "update $this->tableName set $s_camps_valors $whereExp;";
            }

        return $query;
    }
}

