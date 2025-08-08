<?php

namespace gcf\utils;

use DateInterval;
use DatePeriod;
use DateTime;
use IntlDateFormatter;
use Locale;

class Dates
{
    private static array $months = [];

    public static function AnchoredDates(DateTime $start, DateTime $end): array
    {
        $start = clone $start;
        $end = clone $end;

        $out = [];
        $out[] = clone $start;

        $startYear = (int)$start->format('Y');
        $endYear   = (int)$end->format('Y');

        for ($y = $startYear + 1; $y <= $endYear; $y++) {
            $d = (new DateTime())->setDate($y, 1, 1)->setTime(0,0,0);
            if ($d->getTimestamp() <= $end->getTimestamp()) {
                $out[] = $d;
            }
        }

        return $out;
    }

    public static function GetMonths() : array
    {
        if (!empty(self::$months))
            return self::$months;

        $fmt = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::FULL, IntlDateFormatter::FULL,
            date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "LLLL");

        $firstDay = DateTime::createFromFormat("d.m.Y", "01.01.".date('Y'));
        $endDay = DateTime::createFromFormat("d.m.Y", "31.12.".date('Y'));
        $dateInterval = DateInterval::createFromDateString('1 month');
        $periodeMes = new DatePeriod($firstDay, $dateInterval, $endDay);

        self::$months = [];
        foreach($periodeMes as $data)
        {
            self::$months[(int)$data->format('m')] = ucfirst($fmt->format($data));
        }
        return self::$months;
    }

    // Función que devuelve un array de ENTEROS que representa un TimeStamp con el siguiente orden:
    // [0]=>hh, [1]=>mm, [2]=>ss, [3]=>MM, [4]=>DD, [5]=>AAAA
    // Con este formato se puede trabajar con la funcion 'mktime'.

    public static function AryIntTimestamp(string $timestamp) : array
    {
        $aryFecha = [];
        $aryFecha[]=intval(substr($timestamp,9,2));   // hora
        $aryFecha[]=intval(substr($timestamp,12,2));  // minutos
        $aryFecha[]=intval(substr($timestamp,15,2));  // segundos
        $aryFecha[]=intval(substr($timestamp,3,2));   // mes
        $aryFecha[]=intval(substr($timestamp,0,2));   // dia
        $aryFecha[]=intval(substr($timestamp,6,2));   // any
        return $aryFecha;
    }

    public static function DifMinutos(string $timestamp1, string $timestamp2) : float
    {
        if (preg_match("/([0-9]{4}).([0-9]{2}).([0-9]{2})\s(.*)/", $timestamp1, $datae))
        {
            $timestamp1 = $datae[2]."-".$datae[3]."-".substr($datae[1], -2)." ".$datae[4];
        }

        if (preg_match("/([0-9]{4}).([0-9]{2}).([0-9]{2})\s(.*)/", $timestamp2, $datae))
        {
            $timestamp2 = $datae[2]."-".$datae[3]."-".substr($datae[1], -2)." ".$datae[4];
        }

        $aryTimestamp1=self::AryIntTimestamp($timestamp1);
        $aryTimestamp2=self::AryIntTimestamp($timestamp2);

        $intSegundos1=mktime($aryTimestamp1[0],$aryTimestamp1[1],$aryTimestamp1[2],
            $aryTimestamp1[3],$aryTimestamp1[4],$aryTimestamp1[5]);
        $intSegundos2=mktime($aryTimestamp2[0],$aryTimestamp2[1],$aryTimestamp2[2],
            $aryTimestamp2[3],$aryTimestamp2[4],$aryTimestamp2[5]);

        if ($intSegundos1 > $intSegundos2)
            return ($intSegundos1-$intSegundos2)/60;

        return ($intSegundos2-$intSegundos1)/60;
    }
}