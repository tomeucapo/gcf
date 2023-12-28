<?php

namespace gcf\database\drivers;

use Exception;

class errorDatabaseConnection extends Exception
{
    /**
     * @throws Exception
     */
    public function __construct($message = null, $code = 0)
    {
        if(!$message)
            throw new Exception('Desconegut '.get_class($this));

        parent::__construct($message, $code);
    }
}