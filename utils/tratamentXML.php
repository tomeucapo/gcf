<?php
/**
 * tratamentXML.php
 * Llibreria antiga per carregar configuracions XML diverses. Aquesta llibreria esta en fase de extinció.
 */

/**
 * Carrega configuracions de llistats de XML a PHP
 * @param string $xmlFileName
 * @return array
 * @throws Exception
 */
function GetListConfigFromXML(string $xmlFileName): array
{
    if (!file_exists($xmlFileName))
        throw new Exception("No puc trobar el fitxer XML $xmlFileName");

    $xmlDoc = new \DOMDocument();
    if (!$xmlDoc->load($xmlFileName))
        throw new Exception("Error obrint el fitxer XML $xmlFileName");

    $root = $xmlDoc->documentElement;
    $meu_camps = [];

    foreach ($root->childNodes as $taula) {
        if ($taula->nodeName !== "llistat")
            continue;

        $nom_taula = $taula->getAttribute("id");
        $titol = $taula->getAttribute("titol");

        $eleCamp = [];
        foreach ($taula->childNodes as $camp) {
            if ($camp->nodeName === "camp") {
                $nom_camp = $camp->getAttribute("id");
                $nodeArray = [];
                foreach ($camp->childNodes as $value) {
                    if ($value->nodeName !== "#text")
                        $nodeArray[$value->nodeName] = $value->nodeValue;
                }
                $eleCamp[$nom_camp] = $nodeArray;
            }
        }

        $meu_camps[$nom_taula] = ["titol" => $titol,
            "camps" => $eleCamp];
    }
    return ($meu_camps);
}

/**
 * Carrega configuracion de pipelles a una estructura PHP
 * @param string $xmlFileName
 * @return array
 * @throws Exception
 */
function GetTabsConfigFromXML(string $xmlFileName): array
{
    if (!file_exists($xmlFileName))
        throw new Exception("No puc trobar el fitxer XML $xmlFileName");

    $xmlDoc = new \DOMDocument();
    if (!$xmlDoc->load($xmlFileName))
        throw new Exception("Error obrint el fitxer XML $xmlFileName");

    $root = $xmlDoc->documentElement;
    $meu_camps = [];

    foreach ($root->childNodes as $pipelles) {
        if ($pipelles->nodeName == "pipella") {
            $nom_pipella = $pipelles->getAttribute("id");
            $nodeArray = [];
            foreach ($pipelles->childNodes as $value) {
                if ($value->nodeName !== "#text")
                    $nodeArray[$value->nodeName] = $value->nodeValue;
            }

            $meu_camps[$nom_pipella] = $nodeArray;
        }
    }
    return ($meu_camps);
}

/**
 *  Funció utilitzada per passar molts de paràmetres a funcions AJAX. De Javascript
 *  a PHP. Ens tracta un XML amb els paràmetres i ens torna un array amb els
 *  parametres i els seus valors.
 * @param string $xml
 * @return array
 */
function GetFieldsFromXML(string $xml): array
{
    $recodifica = (mb_detect_encoding($xml) == 'UTF-8');
    $xml = str_replace('\\"', '"', $xml);
    $nodeArray = [];

    $xmlDom = new DOMDocument();
    $xmlDom->loadXML($xml);

    $root = $xmlDom->documentElement;
    foreach ($root->childNodes as $node) {
        // TODO: Improve this!
        if ($node->nodeName === '#text')
            continue;

        $nodeArray[strtoupper($node->nodeName)] = ($recodifica) ?
            iconv("UTF-8", "ISO-8859-15", $node->nodeValue) : $node->nodeValue;
    }

    return $nodeArray;
}


