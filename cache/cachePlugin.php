<?php
/**
 * Description of cacheManager
 *
 * @author Tomeu
 */

namespace gcf\cache;

class cacheDriverError extends \Exception {}
class cacheConnectionError extends \Exception {}

abstract class cachePlugin
{
    protected $cacheObj;

    /**
     * Set KEY and value to cache
     * @param $key
     * @param $value
     * @param int $expireTime
     * @return mixed
     */
    abstract public function set($key, $value, $expireTime=0);

    /**
     * Get key/value from cache
     * @param $key
     * @return mixed
     */
    abstract public function get($key);

    /**
     * Delete Key from cache
     * @param $key
     * @return mixed
     */
    abstract public function delete($key);

    /**
     * @param $key
     * @param int $value
     * @return mixed
     */
    abstract public function inc($key, ?int $value=null);

    /**
     * @param $key
     * @param int $value
     * @return mixed
     */
    abstract public function dec($key, ?int $value=null);
}




