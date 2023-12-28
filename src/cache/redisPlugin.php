<?php

/**
 * Created by PhpStorm.
 * User: Tomeu
 * Date: 06/03/2017
 * Time: 22:41
 */

namespace gcf\cache;

use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;

class redisPlugin implements cachePlugin
{
    protected mixed $cacheObj;

    /**
     * redisPlugin constructor.
     * @param array $servers
     * @param ?int $dbIndex
     * @throws cacheDriverError
     * @throws cacheConnectionError
     */
    public function __construct(array $servers, ?int $dbIndex=null)
    {
        if (!class_exists("Redis"))
            throw new cacheDriverError("La llibreria de redis per PHP no esta instal·lada!");

        try {
            if (count($servers) > 1) {
                $this->cacheObj = new RedisCluster(NULL, $servers);
            } else {
                $this->cacheObj = new Redis();
                $srv = array_shift($servers);
                $this->cacheObj->connect($srv->host, $srv->port);

                if($dbIndex)
                    $this->cacheObj->select($dbIndex);
            }

            // Serialitzem amb el serialitzador de PHP
            $this->cacheObj->setOption(Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        } catch (RedisException|RedisClusterException $e) {
            throw new cacheConnectionError("Cache connection error: ".$e->getMessage());
        }
    }

    /**
     * @throws cacheOperationException
     */
    public function set($key, $value, $expireTime=0) : mixed
    {
        try {
            if ($expireTime > 0)
                return $this->cacheObj->set($key, $value, $expireTime);
            return $this->cacheObj->set($key, $value);
        } catch (RedisException $e) {
            throw new cacheOperationException("Set operation error: ".$e->getMessage());
        }
    }

    /**
     * @throws cacheOperationException
     */
    public function get($key) : mixed
    {
        try {
            return $this->cacheObj->get($key);
        } catch (RedisException $e) {
            throw new cacheOperationException("Get operation error: ".$e->getMessage());
        }
    }

    /**
     * @throws cacheOperationException
     */
    public function delete($key): bool
    {
        try {
           return ($this->cacheObj->del($key)>0);
        } catch (RedisException $e) {
            throw new cacheOperationException("Delete operation error: ".$e->getMessage());
        }
    }


    /**
     * @throws cacheOperationException
     */
    public function inc($key, ?int $value=null)
    {
        try {
            if ($value !== null)
                return $this->cacheObj->incrBy($key, $value);
            return $this->cacheObj->incr($key);
        } catch (RedisException $e) {
            throw new cacheOperationException("Increment operation error: ".$e->getMessage());
        }
    }

    /**
     * @throws cacheOperationException
     */
    public function dec($key, ?int $value=null)
    {
        try {
            if ($value !== null)
                return $this->cacheObj->decrBy($key, $value);
            return $this->cacheObj->decr($key);
        } catch (RedisException $e) {
            throw new cacheOperationException("Decrement operation error: ".$e->getMessage());
        }
    }

    /**
     * @throws cacheOperationException
     */
    public function __destruct()
    {
        try {
            if($this->cacheObj->isConnected())
                $this->cacheObj->close();
        } catch (RedisException $e) {
            throw new cacheOperationException("Closing cache connection: ".$e->getMessage());
        }
    }
}