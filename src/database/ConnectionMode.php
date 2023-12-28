<?php

namespace gcf\database;

enum ConnectionMode : string
{
    case PERSISTENT = "P";
    case NORMAL = "N";
}
