<?php

namespace gcf\web\controllers;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AcceptVerbs
{
    public WSMethod $method;

    public function __construct(WSMethod $validMethod)
    {
        $this->method = $validMethod;
    }
}