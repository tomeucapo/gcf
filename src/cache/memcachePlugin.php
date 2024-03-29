<?php
/**
 * Capa de cache per Memcache
 *
 * @author Tomeu Capó
 */

namespace gcf\cache;

use Memcache;

class memcachePlugin implements cachePlugin
{
    protected Memcache $cacheObj;
    private bool $compress;

    /**
     * memcachePlugin constructor.
     * @param array $servers
     * @param bool $compress
     * @param ?int $dbIndex
     * @throws cacheDriverError
     */
    public function __construct(array $servers, bool $compress=true, ?int $dbIndex=null)
    {
         if (!class_exists("Memcache"))
             throw new cacheDriverError("La llibreria de memcache per PHP no esta instal·lada!");

         $this->compress = $compress;
         $this->cacheObj = new \Memcache;
         foreach ($servers as $srv)
                $this->cacheObj->addServer($srv->host, $srv->port);

    }
    
    public function get($key) {
         $flags = null;
         return $this->cacheObj->get($key, $flags);
    }

    public function set($key, $value, $expireTime=0) : mixed
    {
         $flag = ($this->compress) ? MEMCACHE_COMPRESSED : null;
         return $this->cacheObj->set($key, $value, $flag, $expireTime);
    }
    
    public function delete($key) : bool
    {
         return $this->cacheObj->delete($key);
    }

    public function inc($key, ?int $value=null)
    {
        return $this->cacheObj->increment($key);
    }

    public function dec($key, ?int $value=null)
    {
        return $this->cacheObj->decrement($key);
    }
}
