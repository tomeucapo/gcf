<?php

namespace gcf\tests;

use Exception;
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
            $this->db = new DatabaseConnector("localhost:personal_devel.fdb", "tserver", "ts3rv3r", ConnectionMode::NORMAL, "firebird", "SISTEMES");
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @throws errorQuerySQL
     * @throws errorDriverDB
     */
    public function testRelation()
    {
        $consulta = new SQLQuery($this->db);
        $consulta->fer_consulta("select dni_persona from absencia where codi=8");

        if (!$consulta->Eof()) {
            echo "Nom taula = " . $consulta->RelacioField(0)."\n";
            echo "Nom camp = " . $consulta->NomField(0)."\n";
            echo "Tipus camp = " . $consulta->TipusField(0)."\n";
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

    public function tearDown() : void
    {
            $this->db->desconnecta();
    }
}
