<?php

class logger
{
      private $path, $prefix;
      private $f_log, $backupDay;

      public function __construct($path, $prefix, $backupDay=False)
      {
              $this->path = $path;
              $this->prefix = $prefix;
              $this->backupDay = $backupDay;

              $nom_fitxer = $path.$prefix;

              if(file_exists($nom_fitxer))
                 $this->f_log = @fopen($nom_fitxer,"a");
              else
                 $this->f_log = @fopen($nom_fitxer,"wa");

              if (!$this->f_log)
                  throw new Exception("No puc obrir el fitxer $nom_fitxer!");
      }

      public function escriu($linia)
      {
               $liniaF = date("[d/m/Y H:i:s]")." ".getmypid()." ".$linia."\n";
               fputs($this->f_log, $liniaF);

               if($this->backupDay)
               {
                  $fSepar = fopen($this->path.date("Ymd")."_".$this->prefix,"a");
                  fputs($fSepar, $liniaF);
                  fclose($fSepar);
               }
      }

	  public function error($linia)
	  {
			 $this->escriu("[ ERROR ] $linia");
	  }
	 
 	  public function info($linia)
	  {
			 $this->escriu("[ INFO ] $linia");
	  }
     
      public function warning($linia)
      {
			 $this->escriu("[ WARN ] $linia");
      }

	  public function debug($linia)
      {
			 $this->escriu("[ DEBUG ] $linia");
      }

      public function tanca()
      {
               fclose($this->f_log);
      }
/*
      function __destruct()
      {
               if($this->f_log)
                  fclose($this->f_log);
      }
*/
}

?>
