<?php
   require_once "../drivers/dataBaseService.php";
   require_once "../drivers/firebird/service.php";

   class serviceTest extends PHPUnit_Framework_TestCase 
   { 
         protected $srv;
         
         protected function setUp()
         {
                try
                {
                   $this->srv = new dataBaseServiceFirebird("localhost","SYSDBA","moUXB5Lm");
                } catch (Exception $e) {
                   $this->fail($e->getMessage()); 
                }
         }

         public function testInfo()
         {      
//                if (!$this->srv) $this->fail("jjj");         
                print_r($this->srv->info);
         }
         
         public function tearDown()
         {
//                $this->srv->Close();
         }
   }
?>
