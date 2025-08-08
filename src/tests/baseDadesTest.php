<?php

namespace gcf\tests;

use Exception;
use gcf\cache\redisPlugin;
use gcf\database\ConnectionMode;
use gcf\database\DatabaseConnector;
use gcf\database\drivers\errorQuerySQL;
use gcf\database\errorDriverDB;
use gcf\database\SQLQuery;
use PHPUnit\Framework\TestCase;

class baseDadesTest extends TestCase
{
    protected DatabaseConnector $db;
    protected SQLQuery $consulta;

    protected function setUp() : void
    {
        try {
            $this->db = new DatabaseConnector("localhost:personal_devel.fdb", "", "", ConnectionMode::NORMAL, "firebird", "SISTEMES");

            $redisConn = new \stdClass();
            $redisConn->host = "127.0.0.1";
            $redisConn->port = 6379;
            $this->cache = new redisPlugin([ $redisConn ], 1);

        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @throws errorQuerySQL
     * @throws errorDriverDB
     */
    public function testFieldProperties()
    {
        $consulta = new SQLQuery($this->db);
        $consulta->fer_consulta("select dni_persona from absencia where codi=8");

        while(!$consulta->Eof())
        {
            $this->assertEquals("ABSENCIA", $consulta->RelacioField(0));
            $this->assertEquals("DNI_PERSONA", $consulta->NomField(0));
            $this->assertEquals("VARCHAR", $consulta->TipusField(0));

            print_r($consulta->row);

            $consulta->Skip();
        }

        $consulta->tanca_consulta();
    }


    /**
     * @throws errorQuerySQL
     * @throws errorDriverDB
     */
    public function testFieldPropertiesCached()
    {
        $this->db->desconnecta();

        $consulta = new SQLQuery($this->db, $this->cache);
        $consulta->fer_consulta("select dni_persona from absencia where codi=8");

        while(!$consulta->Eof())
        {
            $this->assertEquals("ABSENCIA", $consulta->RelacioField(0));
            $this->assertEquals("DNI_PERSONA", $consulta->NomField(0));
            $this->assertEquals("VARCHAR", $consulta->TipusField(0));

            print_r($consulta->row);

            $consulta->Skip();
        }

        $consulta->tanca_consulta();
    }


    /**
     * @throws errorQuerySQL
     * @throws errorDriverDB
     */
    public function testSelect()
    {
           $consulta = new SQLQuery($this->db);

           $consulta->fer_consulta("select * from menu");

           print "\n";
           while (!$consulta->Eof())
           {
               $this->assertGreaterThanOrEqual(5, $consulta->NumFields());
               for($i=0;$i<$consulta->NumFields();$i++)
                  print $consulta->row[$i]."\t";
               print PHP_EOL;
               $consulta->Skip();
           }
           $consulta->tanca_consulta();
   }

    public function tearDown() : void
    {
            $this->db->desconnecta();
    }
}
