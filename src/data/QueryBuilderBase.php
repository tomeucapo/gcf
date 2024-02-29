<?php

namespace gcf\data;

use app\configurador;
use gcf\cache\cachePlugin;
use Laminas\Log\Logger;

abstract class QueryBuilderBase implements QueryBuilderInterface
{
    protected $tbl;

    protected \stdClass $filtres;

    protected configurador $configurador;

    protected cachePlugin $cache;

    protected ?Logger $logger;

    public function __construct(\stdClass $filtres)
    {
        $this->filtres = $filtres;
        $this->configurador = configurador::getInstance();
        $this->cache = $this->configurador->getCache();
        $this->logger = $this->configurador->getLoggerObject();
    }
}