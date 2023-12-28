<?php

namespace gcf\web\controllers;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public string $template;

    public function __construct(string $template)
    {
        $this->template = $template;
    }
}