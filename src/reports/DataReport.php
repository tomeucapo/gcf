<?php

namespace gcf\reports;

interface DataReport
{        
          public function SetTitle(string $title);
          public function AddSubtitle(string $subTitle);
          public function SetHeaders(ReportHeaders $header);
          public function GetHeaders();
          public function GetData();
}
