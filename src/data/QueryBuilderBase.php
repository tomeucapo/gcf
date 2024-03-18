<?php

namespace gcf\data;

use app\configurador;
use gcf\cache\cachePlugin;
use gcf\database\errorDriverDB;
use gcf\database\models\DataMapper;
use gcf\database\SQLQuery;
use Laminas\Log\Logger;

abstract class QueryBuilderBase implements QueryBuilderInterface
{
    protected ?DataMapper $tbl = null;

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

    /**
     * Execute and return consulta_sql object
     * @throws errorDriverDB
     */
    protected function Execute(): SQLQuery
    {
        $q = new SQLQuery($this->configurador->db);
        $q->PrepareQuery($this->PreparedQuery());
        return $q;
    }
}