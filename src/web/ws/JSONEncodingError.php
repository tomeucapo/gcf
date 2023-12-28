<?php

namespace gcf\web\ws;

class JSONEncodingError extends \Exception
{
    public function __construct($errNumber)
    {
        switch ($errNumber)
        {
            case JSON_ERROR_DEPTH:
                $message = 'Excedido tama�o m�ximo de la pila';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = 'Desbordamiento de buffer o los modos no coinciden';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = 'Encontrado car�cter de control no esperado';
                break;
            case JSON_ERROR_SYNTAX:
                $message = 'Error de sintaxis, JSON mal formado';
                break;
            case JSON_ERROR_UTF8:
                $message = 'Caracteres UTF-8 malformados, posiblemente est�n mal codificados';
                break;
            default:
                $message = 'Error desconocido';
                break;
        }

        parent::__construct($message, $errNumber);
    }
}