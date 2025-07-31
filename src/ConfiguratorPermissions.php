<?php

namespace gcf;

interface ConfiguratorPermissions
{
    public function InitPermissions(int $userId, string $moduleName) : void;
    public function GetPermissionValue(string $level, string $property): ?string;
    public function GetPermissionValues(string $level, string $property): ?array;
}