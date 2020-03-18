<?php
  if (empty($APP_NAME))
      die("APP_NAME not specified, application stopped!");

  $NAME_ENVIRONMENT=getenv("APPS_ENVIRONMENT");
  $ID_ENVIRONMENT=getenv("APPS_ENVIRONMENT_ID");
  $PATH_CONF=getenv("APPS_ENVIRONMENT_CONF");

  $CFG_FILE=$PATH_CONF."/$APP_NAME/properties_".$ID_ENVIRONMENT.".ini";



  include "parseProps.php";

  if (!is_object($config))
     die("La configuracio no s'ha carregat correctament");
 
  // Configuracio dels paths 
  $APP_BASE=$config->paths->path->appbase;

  if (!file_exists($APP_BASE))
     die("El directori base configurat de la aplicacio no existeix: $APP_BASE");

  $INCLUDE_APP = $APP_BASE.DIRECTORY_SEPARATOR;
  $INCLUDE_GCF = $INCLUDE_APP."gcf".DIRECTORY_SEPARATOR;
  $INCLUDE_FRONTAL = $INCLUDE_APP."frontal".DIRECTORY_SEPARATOR."include".DIRECTORY_SEPARATOR;
  $INCLUDE_DATA = $INCLUDE_APP."data";
  
  ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF);
  ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF."database".DIRECTORY_SEPARATOR);
  ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF."utils".DIRECTORY_SEPARATOR);
  ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_GCF."web".DIRECTORY_SEPARATOR);
  ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_FRONTAL);
  ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$INCLUDE_DATA);

  /* Back-compatibility configuration arrays for old-modules style */

  $dirs = ["app"  => $config->paths->path->app ];

  $globals = array ("BASE_DN"        => $config->auth->ldap->basedn,
                    "LDAP_SERVER"    => $config->auth->ldap->host,
                    "NOTIFY_TO"      => $config->general->notifyto,
                    "EXPORTAR_LIMIT" => $config->general->export->limit);

