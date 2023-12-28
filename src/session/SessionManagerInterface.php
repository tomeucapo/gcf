<?php

namespace gcf\session;


interface SessionManagerInterface
{
    public function Start();
    public function End();
    public function Id();
    public function SetSessionID($id);
}