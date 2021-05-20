<?php


namespace gcf\session;


abstract class SessionManagerBase implements SessionManagerInterface
{
    protected $name, $expire;
    protected $data;

    public function __construct(string $name, int $expire)
    {
        $this->name = $name;
        $this->expire = $expire;
    }

    abstract public function Start();
    abstract public function End();
}