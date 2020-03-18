<?php
    /**********************************************************************************
       Funci�feta gr�ies a la necessitat de configurar el modul de diversos.php
	   de manera din�ica sense tocar el codi.
	   Ens permet configurar les taules que podr�mantenir aquest modul aix�com els
	   camps de cada taula. Tot aix�a travers d'un fitxer XML.

       <?xml version="1.0" encoding="ISO-8859-1"?>
       <taules>
            <taula id="categoria" titol="Categoria" order_by="descripcio">
			      <camp id="codi">
                       <titol>Codi</titol>
                       <mida>4</mida>
                       <align>center</align>
                       <str>'</str>
                       <c_html>C_CODI</c_html>
                  </camp>
				  ...
		    </taula>
			...
	   </taules>
	  
	***********************************************************************************/

    if (version_compare(PHP_VERSION,'5','>='))
       require_once('domxml-php4-to-php5.php');

    function ConfigTaulesXML($nom_fitxerXML)  
	{
  		     $xml_doc = domxml_open_file($nom_fitxerXML) or die("No puc obrir el fitxer XML $nom_fitxerXML");

             $root = $xml_doc->document_element();
             $taules = $root->children();	
             $meu_camps = array();
   
             while($taula = array_shift($taules)) {
                   if($taula->tagname != "taula") 	  
                     continue;

		           $nom_taula = $taula->get_attribute("id");
		           $titol = $taula->get_attribute("titol");
	               $order = $taula->get_attribute("order_by");
		   
                   $camps = $taula->children();
                   $eleCamp = array();
		           while($camp = array_shift($camps)) 
				   {
		                 if($camp->tagname == "camp") {
				            $nom_camp = $camp->get_attribute("id");
				            $childnodes = $camp->child_nodes();

                            $nodeArray = array();

  			                foreach ($childnodes as $value) {
  		                            if ($value->node_type() != XML_TEXT_NODE)
				                       $nodeArray[$value->tagname] = $value->get_content();

                            }

                            $eleCamp[$nom_camp] = $nodeArray;
		                 }	   
		           }

           		   $meu_camps[$nom_taula] = array ("titol"    => $titol,
		                                           "camps"    => $eleCamp,
								                   "order_by" => $order); 
             }
             return($meu_camps);
	}
	
    function ConfigLlistatsXML($nom_fitxerXML)  
	{
  		     $xml_doc = domxml_open_file($nom_fitxerXML) or die("No puc obrir el fitxer XML $nom_fitxerXML");

             $root = $xml_doc->document_element();
             $taules = $root->children();	
             $meu_camps = array();
   
             while($taula = array_shift($taules)) {
                   if($taula->tagname == "llistat") {		  
		              $nom_taula = $taula->get_attribute("id");
		              $titol = $taula->get_attribute("titol");
		   
                      $camps = $taula->children();
                      $eleCamp = array();

		              while($camp = array_shift($camps)) {
		                    if($camp->tagname == "camp") {
				               $nom_camp = $camp->get_attribute("id");
				               $childnodes = $camp->child_nodes();

  			                   foreach ($childnodes as $value) {
  		                               if ($value->node_type() != XML_TEXT_NODE)
					                      $nodeArray[$value->tagname] = $value->get_content();

                               }

                               $eleCamp[$nom_camp] = $nodeArray;
		                    }	   
		              }

           		      $meu_camps[$nom_taula] = array ("titol"    => $titol,
		                                              "camps"    => $eleCamp); 
                   }
		
             }
             return($meu_camps);
	}

    function ConfigPipellesXML($nom_fitxerXML)  
	{
  		     $xml_doc = domxml_open_file($nom_fitxerXML) or die("No puc obrir el fitxer XML $nom_fitxerXML");

             $root = $xml_doc->document_element();
             $pipelles = $root->children();	
             $meu_camps = array();
   
             while($taula = array_shift($pipelles)) 
             {
                   if($taula->tagname == "pipella") 
                   {		  
		              $nom_pipella = $taula->get_attribute("id");
                      $camps = $taula->children();

                      foreach ($camps as $value) 
  			          {
  		                       if ($value->node_type() != XML_TEXT_NODE)
					               $nodeArray[$value->tagname] = $value->get_content();
                      }

                      $meu_camps[$nom_pipella] = $nodeArray; 
                   }
             }
             return($meu_camps);
	}

    /*********************************************************************************
      Funci� utilitzada per passar molts de par�metres a funcions AJAX. De Javascript
      a PHP. Ens tracta un XML amb els par�metres i ens torna un array amb els
      parametres i els seus valors.
     *********************************************************************************/

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

    // A partir dels camps tornats de la funcio tracta_xml generam la sentencia
    // SQL de la BD.

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

