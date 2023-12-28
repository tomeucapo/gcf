<?php

/**
 * Simple dicctionary based cache (dummy reasons)
 * User: Tomeu
 * Date: 1/27/2016
 * Time: 1:30 AM
 */

namespace gcf\cache;

class dummyPlugin implements cachePlugin
{
    /**
     * @var array
     */
    private array $store;

    /**
     * @var array
     */
    private array $storeCounters;

    public function __construct()
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

    public function set($key, $value, $expireTime=0) : mixed
    {
        $this->store[$key] = $value;
        return $value;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->storeCounters))
            return $this->store[$key];
        return false;
    }

    public function delete($key) : bool
    {
        unset($this->store[$key]);
        return true;
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