<?php

namespace gcf\reports;

interface DataReport
{        
          public function SetTitle($title);
          public function AddSubtitle($subTitle);
          public function SetHeaders(ReportHeaders $header);
          public function GetHeaders();
          public function GetData();
}
