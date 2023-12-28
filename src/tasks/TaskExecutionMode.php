<?php

namespace gcf\tasks;

enum TaskExecutionMode {
    case NORMAL;
    case BACKGROUND;
}