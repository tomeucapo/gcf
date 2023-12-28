<?php
namespace gcf\database\drivers\postgres;

use gcf\database\drivers\errorQuerySQL;
use gcf\database\drivers\queryBase;

class QuerySQL extends queryBase
{
      public function Skip() : bool
      {
               $this->rowActual++;
               if(!$this->Eof()) 
               {
                  $fetchFunc = $this->assoc ? "pg_fetch_assoc" : "pg_fetch_array";
                  $this->row = @$fetchFunc($this->result, $this->rowActual);
                  return true;
               }
               
               return false;
      }
      
      public function Execute() 
      {
             $this->result = 0;
             $this->rows = 0;
             $this->rowActual = 0;            

             if (!($this->result = @pg_exec($this->connDb,$this->query)))
                throw new errorQuerySQL($this->dataBase->lastError());

             if (preg_match("/^[ \n\r\t]*(select|SELECT)/", $this->query))
             {
                 $this->rows = pg_numrows($this->result);
   	             $this->rowActual = -1;
                 $this->Skip();
             }

             return $this->result; 
      }         

      public function NumFields() : int
      {
             return pg_num_fields($this->result); 
      }

      public function GetFieldName($field)
      {           
             return pg_field_name($this->result, $field); 
      }

      public function GetFieldType($field)
      {
             return pg_field_type($this->result, $field);
      }

      public function GetFieldRelation($field)
      {
             return "";
      }

      public function GetFieldLength($field)
      {
          return pg_field_prtlen($this->result, $field);
      }
 
      public function Commit($idTrans)
      {
             $this->query="commit";
             return $this->Execute();       
      }

      public function Rollback($idTrans)
      {
	     $this->query="rollback";
             return $this->Execute();
      }

      public function BeginTrans()
      {
	     $this->query = "begin";
             return $this->Execute();
      }

      protected function StoreBLOB($fileDescriptor)
      {
             if (!$fileDescriptor)
                return;

             $buffer = stream_get_contents($fileDescriptor);

             $this->BeginTrans();
             $this->oid = pg_lo_create($this->connDb);

             // TODO: Do pg_exec with insert or update, here!

             $handle = pg_lo_open($this->connDb, $this->oid, "w");
             pg_lo_write($handle, $buffer);
             pg_lo_close($handle);
             $this->Commit();
      }

      public function LoadFromBLOB($rowBlob)
      {
             $data = '';
             $this->BeginTrans();
             $lOID = pg_lo_open($this->connDb, $rowBlob, "r");

             while ($blobData = pg_lo_read($lOID, 100))
                   $data.= $blobData;

             pg_lo_close($lOID);
             $this->Commit();

             return $data;
      }
        
      public function Close() 
      {
            if($this->result) 
               @pg_freeresult($this->result); 
  
            $this->rows=-1;
            $this->rowActual=-1;
            $this->result=0;
      }

    public function NextID($generatorID)
    {
        // TODO: Implement NextID() method.
    }
}