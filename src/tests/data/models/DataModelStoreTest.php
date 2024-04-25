<?php

namespace gcf\tests\data\models;

use app\configurador;
use gcf\data\models\DataModelStore;
use gcf\data\models\DataStore;
use PHPUnit\Framework\TestCase;

class DataModelStoreTest extends TestCase
{
    const DB_INDEX = 2;

    private function InitDataStore() : DataModelStore
    {
        $storage = configurador::getInstance()->getCache(self::DB_INDEX);
        $dataStore = new DataStore($storage);

        return new DataModelStore("TEST", $dataStore);
    }

    public function testCreate() : void
    {
        $myTable = $this->InitDataStore();
        $this->assertTrue(
            $myTable->Create(["DESCRIPCIO" => ["TYPE" => "CHAR",
                                               "LEN" => 30]],
                    "Test data table"));
    }

    /**
     * @throws \Exception
     */
    public function testInsert() : int
    {
        $myTable = $this->InitDataStore();

        $newRecord = new MyRecord(["DESCRIPCIO" => "Prova"]);
        $newId = $myTable->Insert($newRecord);
        $this->assertIsInt($newId);

        return $newId;
    }

    /**
     * @depends testInsert
     * @param int $id
     * @return int
     */
    public function testGet(int $id) : int
    {
        $myTable = $this->InitDataStore();

        $record = $myTable->Get($id);

        $this->assertInstanceOf(MyRecord::class, $record);
        $this->assertEquals("Prova", $record->DESCRIPCIO);
        return $id;
    }

    /**
     * @depends testGet
     * @param int $id
     * @return int
     */

    public function testUpdate(int $id) : int
    {
        $myTable = $this->InitDataStore();

        $newRecord = new MyRecord(["DESCRIPCIO" => "Prova modificada"]);
        $myTable->Update($id, $newRecord);

        $modifiedRecord = $myTable->Get($id);
        $this->assertInstanceOf(MyRecord::class, $modifiedRecord);
        $this->assertEquals("Prova modificada", $modifiedRecord->DESCRIPCIO);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return void
     */

    public function testDelete(int $id) : void
    {
        $myTable = $this->InitDataStore();
        $this->assertTrue($myTable->Delete($id));
    }

    /**
     * @throws \Exception
     */
    public function testDescribe() : void
    {
        $myTable = $this->InitDataStore();
        $info = $myTable->Describe();
        $this->assertIsArray($info);

        $this->assertArrayHasKey("DESCRIPTION", $info);
        $this->assertArrayHasKey("FIELDS", $info);

        $this->assertEquals("Tipus d'escriptures", $info["DESCRIPTION"]);
        $this->assertIsArray($info["FIELDS"]);

        foreach ($info["FIELDS"] as $fieldName => $fieldDef)
        {
            $this->assertIsString($fieldName);

            $this->assertArrayHasKey("TYPE", $fieldDef);
            $this->assertIsString($fieldDef["TYPE"]);

            $this->assertArrayHasKey("LEN", $fieldDef);
            $this->assertIsInt($fieldDef["LEN"]);
        }
    }

    /**
     * @return void
     */
    public function testDrop() : void
    {
        $myTable = $this->InitDataStore();

        $this->assertTrue($myTable->Drop());
    }
}
