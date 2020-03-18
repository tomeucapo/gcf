<?php
/*****************************************************
  modul.php

  Definicio de com ha d'esser una classe d'un modul
 *****************************************************/ 

interface modul 
{
          public function Llistat();
          public function pintaForm();
          public function Run();
          public function resultat_html();
}
