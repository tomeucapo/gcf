<?php

namespace gcf\database;

use Serializable;

readonly class DataBaseConnProps implements Serializable
{
    public string $passwd;

    public string $user;
    public string $cadConn;

    public function __construct(string $user, string $passwd, string $cadConn)
    {
            $this->user = $user;
            $this->passwd = $passwd;
            $this->cadConn = $cadConn;
    }

    public function serialize() : string
    {
        return serialize($this->cadConn);
    }

    public function unserialize(string $data) : void
    {
        $this->cadConn = unserialize($data);
    }

    public function __serialize(): array
    {
        return [
            "cadConn" => $this->cadConn
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->cadConn = $data["cadConn"];
    }
}