<?php
namespace gcf\database\drivers\sqlanywhere;

use gcf\database\drivers\errorQuerySQL;
use gcf\database\drivers\queryBase;

class QuerySQL extends queryBase
{
      public function Skip() : bool
      {
             $this->rowActual++;
             if(!$this->Eof()) 
             {
                $this->row = @sasql_fetch_row($this->result);
                return true;
             }
              
             return false;
      }
      
      public function Execute() 
      {
             $this->result = 0;
             $this->rows = 0;
             $this->rowActual = 0;
             
             if (!($this->result = @sasql_query($this->connDb, $this->query)))
                throw new errorQuerySQL($this->dataBase->lastError());
             
             if (preg_match("/^[ \n\r\t]*(select|SELECT)/", $this->query))
             {
                $this->rows = sasql_num_rows($this->result);                 
                $this->rowActual = -1;  
                $this->Skip();
             } 
      }         

      public function NumFields() 
      {
            return sasql_num_fields($this->result);
      }
      
      public function GetFieldName($field)
      {
            $nom_field = '';
            $info_field = sasql_fetch_field($this->result, $field);
            $nom_field  = $info_field->name;
            return $nom_field;
      }

      public function GetFieldLength($field)
      {
            // TODO: Implement this
            return null;
      }

      public function GetFieldType($field)
      {
            $tipusField = '';
            $info_field = sasql_fetch_field($this->result, $field);
            $nom_field  = $tipusField->type;
            return $tipusField;
      }
         
      public function Commit($idTrans)
      {
             return @sasql_commit($this->connDb);  
      }

      public function Rollback($idTrans)
      {
             return @sasql_rollback($this->connDb);
      }
      
      public function Close() 
      {
            if($this->result) {
                 @sasql_free_result($this->result);
            }
            $this->rows=-1;
            $this->rowActual=-1;
            $this->result=0;
      }

    public function NextID($generatorID)
    {
        // TODO: Implement NextID() method.
    }

    protected function StoreBLOB($fileDescriptor)
    {
        // TODO: Implement StoreBLOB() method.
    }

    public function BeginTrans()
    {
        // TODO: Implement BeginTrans() method.
    }

    public function LoadFromBLOB($rowBlob)
    {
        // TODO: Implement LoadFromBLOB() method.
    }

    public function GetFieldRelation($field)
    {
        // TODO: Implement GetFieldRelation() method.
    }
}