<?php
   require_once "../base_dades.php";

   class baseDadesTest extends PHPUnit_Framework_TestCase 
   { 
         protected $db, $consulta;
         
         protected function setUp()
         {
                try
                {
                     $this->db = new base_dades("localhost:personal_devel.fdb", "tserver", "ts3rv3r", "P", "firebird","SISTEMES");

                     $cache = new Memcache;
                     if(!$cache->pconnect("localhost", 11211,30))
                        $this->fail("No hem puc connectar al servidor memcache");

                } catch (Exception $e) {
                     $this->fail($e->getMessage()); 
                }
         }

	     public function testRelation()
		 {
			    $consulta = new consulta_sql($this->db);
                $consulta->fer_consulta("select dni_persona from absencia where codi=8");
				
				if (!$consulta->Eof())
		        {
                    echo "Nom = ".$consulta->RelacioField(0);
//                    echo "Relation = ".$consulta->RelacioField(0);
                }	  
				$consulta->tanca_consulta(); 
		 }
/*
         public function testSelect()
         {      
                if (!$this->db) $this->fail();         
                $consulta = new consulta_sql($this->db);
                
                $consulta->fer_consulta("select * from marcatge");

                print "\n";                
                while (!$consulta->Eof())
                {
                       print $consulta->Record()." ".$consulta->row[0]." ".$consulta->row[2]." ".$consulta->row[10]."\n";
                       $consulta->Skip();
                }
                $consulta->tanca_consulta();
         }

         public function testLastRecord()
         {               
                $this->consulta = new consulta_sql($this->db);
                
                $this->consulta->fer_consulta("select * from empresa");

                if (!$this->consulta->Eof())
                    print $this->consulta->LastRecord();

                $this->consulta->tanca_consulta();
         }
*/
         
         public function tearDown()
         {
                if($this->db)
                    $this->db->desconnecta();
         }
   }
?>
