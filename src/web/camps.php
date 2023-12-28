<?php
/**
 * @deprecated
 * OLD FIELD RENDER FUNCTIONS. All deprecated.
 */

use gcf\database\consulta_sql;
use gcf\database\drivers\errorQuerySQL;

/**
 * @param $param
 * @return string
 * @throws errorQuerySQL
 * @deprecated This function is legacy code, recomends use Dropdown template macro instead
 */
function selectTaula($param)
      {
      		$conn = $param["conn"];
      		$titol = $param["titol"];
      		$nom_camp = $param["nom_camp"];
      		$canvi_taula = $param["canvi_taula"];
      		$estil_camp = $param["estil_camp"];
      		$valor = $param["valor"];
      		$taula = $param["taula"];
      		$camps = $param["camps"];
      		$mostra_codi = $param["mostra_codi"];
      		$clau = $param["clau"];
      		$altres = $param["altres"];

          $out="";
      		if($titol)
               $out = "<label for=\"$nom_camp\">$titol</label>";

	        $out.= "<select id=\"$nom_camp\" name=\"$nom_camp\" onChange=\"$canvi_taula\" style=\"$estil_camp\" $altres>\n";
            $out.= "<option>\n";

               if ($clau)
                   $order=" ORDER BY ".$clau;
               else
                   $order="";

               $query = "SELECT $camps FROM ".$taula.$order.";";

			   $consulta = new consulta_sql($conn);
			   $consulta->fer_consulta($query);

			   while (!$consulta->Eof()) 
			   {
				   $selec="";
				   if (is_array($valor))
				   {
					   if (in_array(trim($consulta->row[0]), $valor))
						   $selec="selected";
				   } else {	   
                   	   if (trim($consulta->row[0])==trim($valor) && strlen($valor)==strlen($consulta->row[0]))
                      	   $selec="selected";
				   }

                   if ($mostra_codi)
                      $codi = $consulta->row[0];
                   else
                      $codi = "";

                   $out.="<option $selec value=\"".$consulta->row[0]."\">$codi ";
                   for($i=1;$i<=$consulta->NumFields();$i++)
				       $out.=$consulta->row[$i]." ";
				   $out.="\n";
				   $consulta->Skip();
               }
               $out.= "</select>\n";
			   $consulta->tanca_consulta();
               return $out;
      }

/**
 * @param $param
 * @return string
 * @deprecated
 */
function selectLlista($param)
{
      		$titol = $param["titol"];
      		$nom_camp = $param["nom_camp"];
      		$canvi_taula = $param["canvi_taula"];
      		$estil_camp = $param["estil_camp"];
      		$seleccio = $param["seleccio"];
      		$taula = $param["taula"];
      		$codi = $param["codi"];
      		$altres = $param["altres"];

            $onChange = ""; $style="";
            if($canvi_taula) $onChange="onChange=\"$canvi_taula\"";
            if($estil_camp) $style="style=\"$estil_camp\"";

            if (!empty($titol))
                $out="<strong>$titol</strong>\n";
            else $out='';

            $out.="<select name=\"$nom_camp\" id=\"$nom_camp\" $onChange $style $altres>\n";

            foreach($taula as $key => $value) 
            {
                   $decids = empty($codi) ? (trim($value)==trim($seleccio)) : ($key==$seleccio);
			       $selec= $decids ? "selected" : "";
               
				   if (empty($codi))
                       $out.= "<option $selec value=\"$value\">$value\n";
				   else
				       $out.= "<option $selec value=\"$key\">$value\n";
            }

            $out.= "</select>\n";
			return $out;
      }

      function camp_llista($titol,$nom_camp,$taula,$seleccio,$codi=false)
      {
	          global $CANVI_TAULA, $ESTIL_CAMP, $ALTRES;
			  
              $out="<strong>$titol</strong>\n";
              $out.="<select name=\"$nom_camp\" id=\"$nom_camp\" onChange=\"$CANVI_TAULA\" style=\"$ESTIL_CAMP\" $ALTRES>\n";

              foreach($taula as $key => $value) {
                   $selec="";		       
				   if (!$codi)
				       $decids = (trim($value)==trim($seleccio));
				   else
				       $decids = ($key==$seleccio); 
			      
				   if ($decids)
                      $selec="selected";
               
				   if (!$codi)
                       $out.= "<option $selec value=\"$value\">$value\n";
				   else
				       $out.= "<option $selec value=\"$key\">$value\n";
              }

              $out.= "</select>\n";

			  return $out;
      }

/**
 * @param $conn
 * @param $titol
 * @param $nom_camp
 * @param $valor
 * @param $taula
 * @param $camps
 * @param $mostra_codi
 * @param $clau
 * @return string
 * @throws errorQuerySQL
 * @deprecated
 */
function camp_de_taula($conn, $titol, $nom_camp, $valor, $taula, $camps, $mostra_codi, $clau)
      {
	       global $CANVI_TAULA, $ESTIL_CAMP, $ALTRES;

          $out="";$selectI="";
           if($titol)
                  $out = "<b>$titol</b>";

	           $out.= "<select id=\"$nom_camp\" name=\"$nom_camp\" onChange=\"$CANVI_TAULA\" style=\"$ESTIL_CAMP\" $ALTRES>\n";
	           if(!trim($valor)) $selectI = "selected";
               $out.= "<option value=\"\" $selectI />\n";
               if ($clau)
                   $order=" ORDER BY ".$clau;
               else
                   $order="";

               $query = "SELECT $camps FROM ".$taula.$order.";";
			   $consulta = new consulta_sql($conn);
			   $consulta->fer_consulta($query);

			   while (!$consulta->Eof()) 
			   {
                   if (trim($consulta->row[0])==trim($valor) && strlen($valor)==strlen($consulta->row[0]))
                      $selec="selected";
                   else
                      $selec="";

                   if ($mostra_codi)
                      $codi = $consulta->row[0];
                   else
                      $codi = "";

                   $out.="<option $selec value=\"".$consulta->row[0]."\" />$codi";
                   for($i=1;$i<count($consulta->row);$i++)
				       $out.=$consulta->row[$i]." ";
				   $out.="\n";
				   $consulta->Skip();
               }
               $out.= "</select>\n";
			   $consulta->tanca_consulta();
               return $out;
      }

/**
 * @param $tipus_out
 * @param $nom
 * @param $contingut
 * @return string
 * @deprecated
 */
      function camp_ocult($tipus_out, $nom, $contingut)
      {
               $output="<input type=\"hidden\" name=\"$nom\"  id=\"$nom\" value=\"$contingut\">\n";
               if($tipus_out!=="stdout")
                   return $output;
               echo $output;
               return "";
      }
