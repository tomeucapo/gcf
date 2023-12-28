<?php

namespace gcf\database\drivers\oracle;

use gcf\database\drivers\errorQuerySQL;
use gcf\database\drivers\queryBase;

class QuerySQL extends queryBase
{
      private $stmt, $r_array;

      public function Skip() 
      {
               $this->rowActual++;
               if(!$this->Eof()) 
               {
                  $fetchFunc = $this->assoc ? "oci_fetch_assoc" : "oci_fetch_array";
                  $this->row = @$fetchFunc($this->stmt);
                  return true;
               }
               
               return false;
      }
      
      public function Execute() 
      {
             $this->result = 0;
             $this->rows = 0;
             $this->rowActual = 0;            
  
             if (!$this->stmt = @OCIParse($this->connDb, $this->query)) 
                 throw new errorQuerySQL($this->dataBase->lastError());
                                      
             if (!@OCIexecute($this->stmt))
             {
                 $error = oci_error($this->stmt);
                 throw new errorQuerySQL($error['message'].": ".$this->query);
             }
    
             if (preg_match("/^[ \n\r\t]*(select|SELECT)/", $this->query)) 
             {
                 $this->rows = ocifetchstatement($this->stmt, $this->r_array);
                 @OCIexecute($this->stmt);
      	         $this->rowActual = -1;  
                 $this->Skip();
             } 
      }         

      public function NumFields() 
      {
             return oci_num_fields($this->stmt);
      }
      
      public function GetFieldName($field)
      {
             return oci_field_name($this->stmt, $field+1);
      }
      
      public function GetFieldType($field)
      {
             return oci_field_type($this->stmt, $field+1);
      }

      public function GetFieldLength($field)
      {
            return oci_field_size($this->stmt, $field+1);
      }

      protected function StoreBLOB($fileDescriptor)
      {
             // TODO: Implements Oracle StoreBLOB method
             return null;
      }

      public function LoadFromBLOB($rowBlob)
      {
             // TODO: Implements Oracle LoadFromBLOB method
             return null;
      }

      public function Close() 
      {
            if($this->stmt || $this->result) 
                @ocifreestatement($this->stmt);
  
            $this->rows=-1;
            $this->rowActual=-1;
            $this->result=0;
      }      

      public function Commit($idTrans)
      {
             // TODO: Implements Oracle Commit method
      }

      public function Rollback($idTrans)
      {
             // TODO: Implements Oracle Rollback method
      }

    public function NextID($generatorID)
    {
        // TODO: Implement NextID() method.
    }

    public function BeginTrans()
    {
        // TODO: Implement BeginTrans() method.
    }

    public function GetFieldRelation($field)
    {
        // TODO: Implement GetFieldRelation() method.
    }
}