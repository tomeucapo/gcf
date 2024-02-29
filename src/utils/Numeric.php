<?php

namespace gcf\utils;

class Numeric
{
    /*
       Redondeig especial d'un valor x:

           pE = |E[x]| , pD = x - pE

           0 < pD < 0.5 => x = pE + 0.5
         0.5 < pD < 1   => x = pE + 1
    */

    public static function redondeigMitg(float $x) : float
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

        return $r;
    }
}