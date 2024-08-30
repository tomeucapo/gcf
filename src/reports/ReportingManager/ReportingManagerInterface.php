<?php

namespace gcf\reports\ReportingManager;

interface ReportingManagerInterface
{
        public function Merge(array $data, string $template, string $format) : ?string;
        public function ListTemplates() : array;
        public function SetTitle(string $titol) : void;
        public function Thumbnails(string $template) : array;
}