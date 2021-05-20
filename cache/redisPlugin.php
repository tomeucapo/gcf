<?php

/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 06/03/2017
 * Time: 22:41
 */

namespace gcf\cache;

class redisPlugin extends cachePlugin
{
    /**
     * @var \Redis
     */

    protected $cacheObj;
    private $compress;

    /**
     * redisPlugin constructor.
     * @param array $servers
     * @param bool $compress
     * @param null $dbIndex
     * @throws cacheDriverError
     */
    public function __construct(array $servers, $compress=true, $dbIndex=null)
    {
        if (!class_exists("Redis"))
            throw new cacheDriverError("La llibreria de redis per PHP no esta instal·lada!");

        $this->compress = $compress;
        $this->cacheObj = new \Redis();

        foreach ($servers as $srv) {
            $this->cacheObj->connect($srv->host, $srv->port);
            break;
        }

        // Serialitzam amb el serialitzador de PHP
        $this->cacheObj->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        if($dbIndex)
           $this->cacheObj->select($dbIndex);
    }

    public function set($key, $value, $expireTime=0)
    {
           if ($expireTime > 0)
               return $this->cacheObj->set($key, $value, $expireTime);

           return $this->cacheObj->set($key, $value);
    }

    public function get($key)
    {
           return $this->cacheObj->get($key);
    }

    public function delete($key)
    {
           return ($this->cacheObj->del($key)>0);
    }

    public function inc($key, ?int $value=null)
    {
        if ($value !== null)
            return $this->cacheObj->incrBy($key, $value);
        return $this->cacheObj->incr($key);
    }

    public function dec($key, ?int $value=null)
    {
        if ($value !== null)
            return $this->cacheObj->decrBy($key, $value);
        return $this->cacheObj->decr($key);
    }

    public function __destruct()
    {
           if ($this->cacheObj instanceof \Redis)
               $this->cacheObj->close();
    }
}