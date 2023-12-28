<?php
/**
 * Description of cacheManager
 *
 * @author Tomeu
 */

namespace gcf\cache;

interface cachePlugin
{
    /**
     * Set KEY and value to cache
     * @param $key
     * @param $value
     * @param int $expireTime
     * @return mixed
     */
    public function set($key, $value, $expireTime): mixed;

    /**
     * Get key/value from cache
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * Delete Key from cache
     * @param $key
     * @return mixed
     */
    public function delete($key) : bool;

    /**
     * @param $key
     * @param int $value
     * @return mixed
     */
    public function inc($key, ?int $value=null);

    /**
     * @param $key
     * @param int $value
     * @return mixed
     */
    public function dec($key, ?int $value=null);
}




