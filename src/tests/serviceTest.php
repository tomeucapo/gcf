<?php
namespace gcf\tests;
use Exception;
use gcf\database\drivers\firebird\DataBaseService;
use PHPUnit\Framework\TestCase;

class serviceTest extends TestCase
{
    protected DataBaseService $srv;

    protected function setUp() : void
    {
        try {
            $this->srv = new DataBaseService("localhost", "SYSDBA", "moUXB5Lm");
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testInfo()
    {
        print_r($this->srv->info);
    }

    public function tearDown() : void
    {
           $this->srv->Close();
    }
}
