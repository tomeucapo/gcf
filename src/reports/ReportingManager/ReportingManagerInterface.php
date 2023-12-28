<?php

namespace gcf\reports\ReportingManager;

interface ReportingManagerInterface
{
        public function Merge($data, $template, $format);
        public function ListTemplates();
        public function SetTitle(string $titol);
        public function Thumbnails($template) : array;
}