<?php

/**
 * Simple dicctionary based cache (dummy reasons)
 * User: Tomeu
 * Date: 1/27/2016
 * Time: 1:30 AM
 */

namespace gcf\cache;

class dummyPlugin extends cachePlugin
{
    /**
     * @var array
     */
    private $store;

    /**
     * @var array
     */
    private $storeCounters;

    public function __construct($stuff)
    {
        $this->store = [];
        $this->storeCounters = [];

        if (file_exists("dummyCache.dat")) {
            $fp = fopen("dummyCache.dat", "rb");
            $dataFile = fgets($fp);
            if ($dataFile)
                $this->store = unserialize($dataFile);
            fclose($fp);
        }
    }

    public function set($key, $value, $expireTime=0)
    {
        $this->store[$key] = $value;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->storeCounters))
            return $this->store[$key];
        return false;
    }

    public function delete($key)
    {
        unset($this->store[$key]);
    }

    public function inc($key, ?int $value=null)
    {
        if (array_key_exists($key, $this->storeCounters))
            $this->storeCounters[$key]++;
        else $this->storeCounters[$key] = 0;
    }

    public function dec($key, ?int $value=null)
    {
        if (array_key_exists($key, $this->storeCounters))
        {
            if ($this->storeCounters[$key] > 0)
                $this->storeCounters[$key]--;
        } else $this->storeCounters[$key] = 0;
    }

    public function __destruct()
    {
        $fp = fopen("dummyCache.dat","w");
        fputs($fp, serialize($this->store));
        fclose($fp);
    }
}