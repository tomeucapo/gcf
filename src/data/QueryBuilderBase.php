<?php

namespace gcf\data;

use app\configurador;
use gcf\cache\cachePlugin;

abstract class QueryBuilderBase implements QueryBuilderInterface
{
    protected $tbl;

    protected \stdClass $filtres;

    /**
     * @var configurador
     */
    protected configurador $configurador;

    /**
     * @var cachePlugin
     */
    protected cachePlugin $cache;

    /**
     * @var \Zend_Log|null
     */
    protected $logger;

    public function __construct(\stdClass $filtres)
    {
        $this->filtres = $filtres;
        $this->configurador = configurador::getInstance();
        $this->cache = $this->configurador->getCache();
        $this->logger = $this->configurador->getLoggerObject();
    }
}