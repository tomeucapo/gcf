<?php

namespace gcf\database\drivers;

use Exception;

class errorQuerySQL extends Exception
{
    private ?string $sql;

    public function __construct(string $message, ?string $sql=null)
    {
        $this->message = $message;
        $this->sql = $sql;

        parent::__construct($message);
    }

    public function getSQLSentence() : ?string
    {
        return ($this->sql);
    }

    public function isConnectionError() : bool
    {
        return ( preg_match("/database .+ shutdown/", $this->message) ||
            str_ends_with($this->message, "Broken pipe") ||
            str_starts_with($this->message, "Unable to complete network request"));
    }

    public function isTooManyHandles() : bool
    {
        return (  preg_match("/too many open handles/", $this->message) );
    }

    public function isInvalidRequest() : bool
    {
        return (  preg_match("/invalid request handle/", $this->message) );
    }

    public function isUnrecoverable() : bool
    {
        return ($this->isTooManyHandles() || $this->isConnectionError() || $this->isInvalidRequest());
    }
}
