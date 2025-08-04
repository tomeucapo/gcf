<?php
namespace gcf\web\controllers;

use gcf\utils\JsonHelper;

trait modulConfig
{
    protected function LoadConfig(string $name) : array
    {
        if (!file_exists($this->configurador->dirs["module_cfgs"] . $name . ".json"))
            return [];

        $cache = $this->configurador->getCache();

        $cachedCfg = $cache->get("MODULE_CFG:".$name);
        if ($cachedCfg !== false)
            return $cachedCfg;

        $fileConf = file_get_contents($this->configurador->dirs["module_cfgs"] . $name . ".json");
        $config = JsonHelper::decode(utf8_encode($fileConf));

        $cache->set("MODULE_CFG:$name", $config, 3600);

        return $config;
    }
}