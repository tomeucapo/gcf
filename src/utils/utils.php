<?php
/*
    utils.php
    Funcions globals a tota l'aplicació. Aquest modul de cada vegada estarà en desus!

    TCC 2006 (C)
*/


use gcf\web\templates\templateEngine;
use gcf\database\consulta_sql;

function parseRequestHeaders()
{
    $headers = array();
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;
}


/*
   Redondeig especial d'un valor x:

       pE = |E[x]| , pD = x - pE

       0 < pD < 0.5 => x = pE + 0.5
     0.5 < pD < 1   => x = pE + 1
*/

    function redondeigMitg($x)
    {
        $valor    = abs($x);
        $pEntera  = intval($valor);
        $pDecimal = $valor - $pEntera;

        if(($pDecimal>0) && ($pDecimal<0.5))
           $r = $pEntera + 0.5;
        else if(($pDecimal>0.5) && ($pDecimal<1))
           $r = $pEntera + 1;
        else $r = $valor;

        if($x < 0)
           $r = (-1)*$r;

        return($r);
    }

    function calc_num_dies($data_inici, $data_final) 
    {
               $anyo1 = strtok(trim($data_inici), "-");
               $mes1 = strtok("-");
               $dia1 = strtok("-");

               $anyo2 = strtok(trim($data_final), "-");
               $mes2 = strtok("-");
               $dia2 = strtok("-");

               if ($anyo1 < $anyo2)
               {
                   $dias_anyo1 = date("z", mktime(0,0,0,12,31,$anyo1)) - date("z", mktime(0,0,0,$mes1,$dia1,$anyo1));
                   $dias_anyo2 = date("z", mktime(0,0,0,$mes2,$dia2,$anyo2));
                   $num_dias = $dias_anyo1 + $dias_anyo2;
               } else
                   $num_dias = date("z", mktime(0,0,0,$mes2,$dia2,$anyo2)) - date("z", mktime(0,0,0,$mes1,$dia1,$anyo1));

               return $num_dias;
    }

    function afegeix_boto(templateEngine $tmpl, $nom_tmpl, $ENLLAC, $IMATGE, $HINT)
    {
             $tmpl->addVars($nom_tmpl, array("ENLLAC" => $ENLLAC,
                                             "IMATGE" => $IMATGE,
                                             "HINT"   => $HINT));
             $tmpl->parseTemplate($nom_tmpl, 'a');

             $tmpl->setAttribute($nom_tmpl, "visibility", "show");

             return $tmpl;               
    }

    function caixa_missatge($msg)
    {
	    global $dirs;
		
        $HTML_CODE = '<table class="container">';
        $HTML_CODE.= '    <tr><td><img src="'.$dirs["imatges"].'messagebox_info.png"></td>';
        $HTML_CODE.= '        <td>&nbsp;&nbsp;&nbsp;'.$msg.'</td></tr></table><br><br>';

        return ($HTML_CODE);
    }

    function trans_data($data)
	{
             if(preg_match("/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/", $data, $dataConv))
                $retval = sprintf("%02d/%02d/%4d",$dataConv[2],$dataConv[1],$dataConv[3]);
             else $retval = $data;

	  	     return($retval);
    }

/**
 * @param $db
 * @param $C_NOM_CAMP
 * @param $C_CENTRE
 * @param $C_DEPART
 * @return string
 * @throws errorQuerySQL
 */
	function taula_departs_centre($db, $C_NOM_CAMP, $C_CENTRE, $C_DEPART)
	{
    	     $query_dep = "centre cen, centre_dep cd ";
             $query_dep.= "right join departament dep on cd.codi_depart = dep.codi ";
             $query_dep.= "where cen.codi = '$C_CENTRE' and cen.codi = cd.codi_centre";

			 return camp_de_taula($db,"",$C_NOM_CAMP,$C_DEPART,$query_dep,"dep.codi, dep.descripcio",false,"dep.descripcio");
	}

/**
 * Funció per extreure una llista resum d'anys d'una taula.
 * Donat una taula i el seu camp data, ens torna una llista amb tots els anys
 * existents dins la taula.
 * @param $conn_db
 * @param $taula
 * @param $camp_data
 * @param $where
 * @return array
 * @throws errorQuerySQL
 */
    function ExtreuAnys($conn_db, $taula, $camp_data, $where)
    {
         $llista_anys = array();

         if ($where) 
             $where = "where ".$where;

         $cons = new consulta_sql($conn_db);         
         $cons->fer_consulta("select extract(year from $camp_data) from $taula $where group by 1;");

	 while (!$cons->Eof()) 
	 {     
                array_push($llista_anys, $cons->row[0]);                  
                $cons->Skip();
         }

         if(!in_array(date("Y"), $llista_anys))
            array_push($llista_anys, date("Y"));

         if(!in_array(date("Y")+1, $llista_anys))
            array_push($llista_anys, date("Y")+1);

         $cons->tanca_consulta();
         return $llista_anys;
     }

   /**********************************************
   **   FUNCIONES DE TRATAMIENTO DE MARCAJES  ***
   **********************************************/

   // Funci� que devuelve un array de ENTEROS que representa un TimeStamp con el siguiente orden:
   // [0]=>hh, [1]=>mm, [2]=>ss, [3]=>MM, [4]=>DD, [5]=>AAAA
   // Con este formato se puede trabajar con la funcion 'mktime'.

   function AryIntTimestamp($timestamp)
   {
      $aryFecha=array();
      $aryFecha[]=intval(substr($timestamp,9,2)); // hora
      $aryFecha[]=intval(substr($timestamp,12,2));  // minutos
      $aryFecha[]=intval(substr($timestamp,15,2));  // segundos
      $aryFecha[]=intval(substr($timestamp,3,2));  // mes
      $aryFecha[]=intval(substr($timestamp,0,2));  // dia
      $aryFecha[]=intval(substr($timestamp,6,2));  // any
      return($aryFecha);
   }

   function DifMinutos($timestamp1, $timestamp2)
   {
	 		if (preg_match("/([0-9]{4}).([0-9]{2}).([0-9]{2})\s(.*)/", $timestamp1, $datae))
        	{
                $timestamp1 = $datae[2]."-".$datae[3]."-".substr($datae[1], -2)." ".$datae[4];
			}

			if (preg_match("/([0-9]{4}).([0-9]{2}).([0-9]{2})\s(.*)/", $timestamp2, $datae))
            {   
                $timestamp2 = $datae[2]."-".$datae[3]."-".substr($datae[1], -2)." ".$datae[4];
            }
	
            $aryTimestamp1=AryIntTimestamp($timestamp1);
            $aryTimestamp2=AryIntTimestamp($timestamp2);

            $intSegundos1=mktime($aryTimestamp1[0],$aryTimestamp1[1],$aryTimestamp1[2],
                                 $aryTimestamp1[3],$aryTimestamp1[4],$aryTimestamp1[5]);
            $intSegundos2=mktime($aryTimestamp2[0],$aryTimestamp2[1],$aryTimestamp2[2],
                                 $aryTimestamp2[3],$aryTimestamp2[4],$aryTimestamp2[5]);

            if ($intSegundos1 > $intSegundos2)
                return ($intSegundos1-$intSegundos2)/60;

            return ($intSegundos2-$intSegundos1)/60;   
   }

   function Quitar_basura($texto)
   {
        $texto = strtr($texto, '"', ' ');
        $texto1 = strtr($texto, "���������������������������Ѻ�",
              "aaeeiiioouuuaaeeiiioouuuccnnoa");
        return trim($texto1);
   }

   if (!function_exists("readline")) 
   {
      function readline($prompt="") 
      {
        echo $prompt;
        $o = "";
        $c = "";
        while (($c!="\r") && ($c!="\n")) 
        {
               $o.= $c;
               $c = fread(STDIN, 1);
        }
        //fgetc(STDIN);
        return $o;
     }
  }
  /* Funcio per escursar les paraules d'una frase fins a escursar la propia frase fins a la longitud desitjada */

  function abreviaFrase($frase, $maxLength)
  {
         if(strlen($frase)>$maxLength)
         {
            $pals = preg_split("/[\s,]/", $frase); $maxPals = count($pals);
            $i = 0; $fraseNova = $frase;

            while((strlen($fraseNova)>=$maxLength) && ($i<=$maxPals))
            {
                  if(strlen($pals[$i])<=2) 
                  {
                     if(!preg_match("/^[0-9]/", $pals[$i])) 
                        $pals[$i]='';$i++;
                  } 
                  else
                  {
                     if(strlen($pals[$i])<=3)
                        $i++;
                     else 
                        $pals[$i] = substr($pals[$i], 0, -1);
                  }

                  $fraseNova = implode(" ", $pals);
            }

            $frase = $fraseNova;
        }

        return($frase);
  }

  /***********************************************
   **     FUNCI� DE MANEIG D'ERRORS GREUS       **
   ***********************************************/

  function errorHandle($type, $msg, $file, $line)
  {
    switch($type){
      case E_ERROR:
           ?>
              <SCRIPT>alert('<?php echo $msg; ?>');</SCRIPT>
           <?php
      case E_WARNING: 
            if (strstr($msg,"no permission"))
            {
                preg_match("/TABLE (.*)/", $msg, $nom_taula);
                echo "ERROR: No tens privilegis per realitzar aquesta operaci�: ".$nom_taula[1];
                exit;
            } else
               if (strstr($msg,"Token unknown")){
                  if(!$_POST["rs"])
                  {
                    ?><SCRIPT>alert('Error de base de dades: <? echo $msg; ?>');</SCRIPT><?php 
                  } else echo "ERROR: ".$msg;
               }
            break; 
      case E_PARSE:
        if (strstr($msg,"Password")){
          // Error en autentificacio!
          ?>
            <script language="javascript">
              location.href="main.php";
            </script>
          <?php
        }
        if (strstr($msg,"permission denied")){
            preg_match("/TABLE (.*)/", $msg, $nom_taula); 
          ?>
            <SCRIPT type="text/Javascript">alert("No tens permís per realitzar aquesta operació: (<?php echo $nom_taula[1]; ?>)");</SCRIPT>
          <?php
        }
        break;
      default:
        break;
    }
  }
