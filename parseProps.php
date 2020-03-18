<?php
   include "connectionPool.php";

   use Laminas\Config;

   try {
       $configObj = new Config\Reader\Ini();
       $configParameters = $configObj->fromFile($CFG_FILE);
       $config = new Config\Config($configParameters);
   } catch(Config\Exception\RuntimeException $e) {
       die("No puc llegir la configuracio: ".$e->getMessage());
   }

   // Inicialitzam les configuracions de les bases de dades
   $dbs = preg_split("/,/", $config->general->databases);
   $dbMain = $config->general->maindb;

   // Preinicialitza les connexions de BBDD que hi ha definides al fitxer de configuració
   $dbPool = connectionPool::getInstance($config->general->maindb);
   foreach($dbs as $dbname) 
   {
	try {
           $dbPool->$dbname = new connectionDb($config->$dbname->database);
	} catch(Exception $e) {
	       error_log("Parse props error: ".$e->getMessage());
	}
   }