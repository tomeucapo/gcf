<?php

namespace gcf\web;

use gcf\web\controllers\WSMethod;

abstract class RouterRequestBase
{
    public mixed $filtres;
    public mixed $id = null;
    public mixed $data;

    public WSMethod $method;

    public function __construct(string $method, $id, $filtres, $data)
    {
        $this->id = $id;
        $this->filtres = $filtres;
        $this->data = $data;

        match($method)
        {
            "GET" => $this->method = WSMethod::GET,
            "PUT" => $this->method = WSMethod::PUT,
            "POST" => $this->method = WSMethod::POST,
            "DELETE" => $this->method = WSMethod::DELETE
        };
    }
}