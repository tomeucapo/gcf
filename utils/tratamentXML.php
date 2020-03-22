<?php
/**
 * tratamentXML.php
 * Llibreria antiga per carregar configuracions XML diverses. Aquesta llibreria esta en fase de extinció.
 */

/**
 * Carrega configuracions de llistats de XML a PHP
 * @param $nom_fitxerXML
 * @return array
 * @throws Exception
 */
    function ConfigLlistatsXML($nom_fitxerXML)  
	{
	         if (!file_exists($nom_fitxerXML))
	             throw new Exception("No puc trobar el fitxer XML $nom_fitxerXML");

  		     $xmlDoc = new \DOMDocument();
  		     if (!$xmlDoc->load($nom_fitxerXML))
  		         throw new Exception("Error obrint el fitxer XML $nom_fitxerXML");

  		     $root = $xmlDoc->documentElement;
             $meu_camps = [];

             foreach($root->childNodes as $taula)
             {
                   if($taula->nodeName == "llistat")
                   {
		              $nom_taula = $taula->getAttribute("id");
		              $titol = $taula->getAttribute("titol");

                      $eleCamp = [];
                      foreach($taula->childNodes as $camp)
                      {
		                    if($camp->nodeName === "camp")
		                    {
				               $nom_camp = $camp->getAttribute("id");
				               $nodeArray = [];
  			                   foreach ($camp->childNodes as $value)
  			                   {
  		                               if ($value->nodeName !== "#text")
					                      $nodeArray[$value->nodeName] = $value->nodeValue;
                               }
                               $eleCamp[$nom_camp] = $nodeArray;
		                    }	   
		              }

           		      $meu_camps[$nom_taula] = ["titol"    => $titol,
                                                "camps"    => $eleCamp];
                   }
		
             }
             return($meu_camps);
	}

/**
 * Carrega configuracion de pipelles a una estructura PHP
 * @param $nom_fitxerXML
 * @param $var
 * @return array
 * @throws Exception
 */
    function ConfigPipellesXML($nom_fitxerXML)
	{
	        if (!file_exists($nom_fitxerXML))
                throw new Exception("No puc trobar el fitxer XML $nom_fitxerXML");

            $xmlDoc = new \DOMDocument();
            if (!$xmlDoc->load($nom_fitxerXML))
                throw new Exception("Error obrint el fitxer XML $nom_fitxerXML");

             $root = $xmlDoc->documentElement;
             $meu_camps = [];

             foreach($root->childNodes as $pipelles)
             {
                   if($pipelles->nodeName == "pipella")
                   {		  
		              $nom_pipella = $pipelles->getAttribute("id");
		              $nodeArray = [];
                      foreach ($pipelles->childNodes as $value)
  			          {
  		                       if ($value->nodeName !== "#text")
					               $nodeArray[$value->nodeName] = $value->nodeValue;
                      }

                      $meu_camps[$nom_pipella] = $nodeArray; 
                   }
             }
             return($meu_camps);
	}

/**
 *  Funci� utilitzada per passar molts de par�metres a funcions AJAX. De Javascript
 *  a PHP. Ens tracta un XML amb els par�metres i ens torna un array amb els
 *  parametres i els seus valors.
 * @param $dades_xml
 * @return array
 */
    function tracta_xml($dades_xml) 
    {
             $recodifica = (mb_detect_encoding($dades_xml) == 'UTF-8');
             $dades_xml = str_replace('\\"','"', $dades_xml);
             $nodeArray = [];

             $xmlDom = new DOMDocument();
             $xmlDom->loadXML($dades_xml);

             $root = $xmlDom->documentElement;
             foreach ($root->childNodes as $node)
             {
					  // TODO: Improve this!
                      if ($node->nodeName==='#text')
						 continue;

                      $nodeArray[strtoupper($node->nodeName)] = ($recodifica) ?
                              iconv("UTF-8", "ISO-8859-15", $node->nodeValue) : $node->nodeValue;
             }

             return $nodeArray;
    }

/**
 * A partir dels camps tornats de la funcio tracta_xml generam la sentencia
 * SQL de la BD.
 * @param array $camps
 * @param $tipus
 * @param $taula
 * @param string $camp_clau
 * @param string $valor_codi
 * @param string $cond
 * @param bool $swapDate
 * @return string
 */
    function MontaSQL(array $camps, $tipus, $taula, $camp_clau='', $valor_codi='', $cond='', $swapDate=true)
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

                        if (trim($valor)!='')
                        {
                            if($swapDate)
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

                        if($tipus == "INSERT") {
                            $valorsCamps[] = $valor;
                        } else if($tipus == "UPDATE") {
                            $valorsCamps[] = "$nom_camp = ".$valor;
                        }
                }

                $query = '';
                $s_camps_valors = implode(",",$valorsCamps);

                if ($tipus === "INSERT")
                {
                    $query = "insert into $taula (".implode(",", $s_camps_db).") values ($s_camps_valors);";
                } else 
                    if ($tipus === "UPDATE")
                    {
                       if(empty($cond))
                          $cond = "$camp_clau = $valor_codi";
                       $query = "update $taula set $s_camps_valors where $cond;";
                    }

                return $query;
    }

