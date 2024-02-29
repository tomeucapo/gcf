<?php

namespace gcf\utils;

class Strings
{
    public static function ClearText(string $texto) : string
    {
        $texto = strtr($texto, '"', ' ');
        $texto1 = strtr($texto, "���������������������������Ѻ�",
            "aaeeiiioouuuaaeeiiioouuuccnnoa");
        return trim($texto1);
    }

    /* Funcio per escursar les paraules d'una frase fins a escursar la propia frase fins a la longitud desitjada */

    public static function ShortenSentence(string $frase, int $maxLength) : string
    {
        if(strlen($frase)<=$maxLength)
            return $frase;

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

        return $fraseNova;
    }
}