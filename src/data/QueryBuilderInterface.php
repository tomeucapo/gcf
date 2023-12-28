<?php

namespace gcf\data;

interface QueryBuilderInterface
{
    public function PrepareWhereCondition() : string;
    public function PreparedQuery(): string;
}