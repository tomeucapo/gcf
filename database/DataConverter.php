<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 6/19/2018
 * Time: 9:06 PM
 */

namespace gcf\database;

class DataConverter implements ConverterType
{
    private $tableName;
    private $swapDate;
    private $generalWhere;
    public $dataFields;

    public function __construct(\taulaBD $table, $swapDate = null)
    {
            $this->tableName = $table->nomTaula;
            $this->generalWhere = "";
            $this->dataFields = [];

            if ($swapDate === null)
                $this->swapDate = ($table->getConnection()->drv === "firebird");
            else $this->swapDate = $swapDate;
    }

    /**
     * Read XML data and create dictionary field => value
     * @param $dades_xml
     * @return array
     */
    public static function ReadDataXML($dades_xml)
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
     * @param $cond
     */
    public function Where($cond)
    {
            $this->generalWhere = $cond;
    }

    /**
     * Convert XML data to SQL sentence
     * @param $type
     * @param $dataXML
     * @return string
     */
    public function XMLToSQL($type, $dataXML)
    {
        $this->dataFields = self::ReadDataXML($dataXML);
        return $this->ArrayToSQL($type, $this->dataFields);
    }

    /**
     * A partir dels camps tornats de la funcio tracta_xml generam la sentencia
     * SQL de la BD.
     *
     * @param string $tipus Tipus de operació a generar
     * @param array $camps Llista de camps i els seus valors
     * @return string Sentencia SQL generada
     */
    public function ArrayToSQL($tipus, array $camps)
    {
        $tipus = strtoupper($tipus);
        $valorsCamps = [];
        $s_camps_db = [];

        foreach($camps as $nom_camp => $valor)
        {
            $valor=str_replace("'", "''", $valor);
            $valor=str_replace("\'", "''", $valor);

            if (trim($nom_camp)=='')
                continue;

            $s_camps_db[] = $nom_camp;

            if (trim($valor)!=='')
            {
                if($this->swapDate)
                {
                    if(preg_match("/^([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4})/", $valor, $data))
                    {
                        if (preg_match("/([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})$/", $valor, $hora))
                            $valor = "'".trans_data($data[1])." ".$hora[1]."'";
                        else $valor = "'".trans_data($data[1])."'";
                    } else if($valor!='?')                // Si un camp te per valor un ? �s que li passam un blob
                        $valor = "'".$valor."'";
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
                   $whereExp = "where {$this->generalWhere}";
                $query = "update $this->tableName set $s_camps_valors $whereExp;";
            }

        return $query;
    }
}

