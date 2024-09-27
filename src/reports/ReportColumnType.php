<?php

namespace gcf\reports;

use JsonSerializable;

enum ReportColumnType implements JsonSerializable
{
    case GENERAL_FORMAT;
    case NUMBER_FORMAT;

    public function jsonSerialize(): int {
        return match($this) {
            ReportColumnType::GENERAL_FORMAT => 0,
            ReportColumnType::NUMBER_FORMAT => 1
        };
    }
}