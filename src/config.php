<?php

use gcf\Environment;

if (empty($APP_NAME))
      die("APP_NAME not specified, application stopped!");

try {
    $env = Environment::getInstance($APP_NAME);
} catch (Exception $ex) {
    die($ex->getMessage());
}

$config = $env->config;

// Configuracio dels paths
$APP_BASE=$config->paths->path->appbase;

if (!file_exists($APP_BASE))
   die("El directori base configurat de la aplicacio no existeix: $APP_BASE");

$INCLUDE_APP = $APP_BASE.DIRECTORY_SEPARATOR;
$INCLUDE_GCF = $INCLUDE_APP."gcf".DIRECTORY_SEPARATOR;

// TODO: Esta en fase de extinció
ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF);
ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF."utils".DIRECTORY_SEPARATOR);
ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF."web".DIRECTORY_SEPARATOR);
