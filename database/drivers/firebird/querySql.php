<?php

class querySQLFirebird extends queryBase
{
      private $myEof, $hndTrans;

      public function __construct(dataBaseConn $db)
      {
             parent::__construct($db);
      }

      final public function Eof() 
      {
            return($this->myEof);
      }
     
      public function Skip() 
      {
             $this->rowActual++;
             if(!$this->Eof()) 
             {
                $fetchFunc = $this->assoc ? "ibase_fetch_assoc" : "ibase_fetch_row";
                if(!($this->row = @$fetchFunc($this->result)))
                   $this->myEof = true;
                return true;
             }
              
             return false;
      }

    /**
     * @return resource
     * @throws errorQuerySQL
     */
      public function Execute()
      {
             $this->result = 0;
             $this->rows = 0;
             $this->rowActual = 0;

             // Si existeix una transaccio iniciada, aleshores executam aquesta sentencia dins la TX
             if ($this->hndTrans)
                 $cnx = $this->hndTrans;
             else $cnx = $this->connDb;

             if ($this->blobID)
                 $this->result = @ibase_query($cnx, $this->query, $this->blobID);             
             else
                 $this->result = @ibase_query($cnx, $this->query);

             if (!$this->result)
             {
                 $this->error = $this->dataBase->lastError();
                 throw new errorQuerySQL($this->dataBase->lastError(), $this->query);
             }
             
             if (preg_match("/^[ \n\r\t]*(select|SELECT)/", $this->query))
             {
                $this->myEof = false;                 
                $this->rowActual = -1;  
                $this->Skip();
             }

             return $this->result; 
      }         

      final public function LastRecord() 
      {
            $i=0;
            $res = @ibase_query($this->connDb, $this->query);
            while(@ibase_fetch_row($res))
                  $i++;
            ibase_free_result($res);
            return $i;
      }
      
      public function NumFields() 
      {
            return ibase_num_fields($this->result);
      }
      
      public function GetFieldName($field)
      {       
            $info_field = ibase_field_info($this->result, $field);
            return $info_field['name'];
      }
      
      public function GetFieldType($field)
      {          
            $info_field = ibase_field_info($this->result, $field);            
            return $info_field['type'];
      }

      public function GetFieldRelation($field)
      {     
            $info_field = ibase_field_info($this->result, $field);
            return $info_field['relation'];
      }
      
      public function GetFieldAlias($field)
      {     
            $info_field = ibase_field_info($this->result, $field);
            return $info_field['alias'];
      }

      public function GetFieldLength($field)
      {
            $info_field = ibase_field_info($this->result, $field);
            return $info_field['length'];
      }

      protected function StoreBLOB($fileDescriptor)
      {
            return @ibase_blob_import($this->connDb, $fileDescriptor);
      }
      
      public function LoadFromBLOB($rowBlob)
      {
            $data = '';            
            $blobHandler = @ibase_blob_open($this->connDb, $rowBlob);

            if (!$blobHandler)
               return $data;

            while($blobData = @ibase_blob_get($blobHandler, 100)) 
                  $data.= $blobData;

            @ibase_blob_close($blobHandler);
            return $data;
      }

      public function Commit($idTrans=null) 
      {
			 // Si ens pasen un ID de transsaccio llavors commitam aquella transaccio
			 // si no, commitam totes les transaccions

             $this->hndTrans = null;
             if (!$idTrans)
	             return @ibase_commit($this->connDb);  
			 return @ibase_commit($idTrans);
      }

      public function Rollback($idTrans=null) 
      {
             $this->hndTrans = null;
             if (!$idTrans)
	             return @ibase_rollback($this->connDb);
			 return @ibase_rollback($idTrans);
      }

    /**
     * @return resource
     * @throws errorTransSQL
     */
      public function BeginTrans()
      {
             $this->hndTrans = null;
             if (!($hndTrans = @ibase_trans(IBASE_CONCURRENCY, $this->connDb)))
				throw new errorTransSQL($this->dataBase->lastError());

             $this->hndTrans = $hndTrans;
             return $hndTrans;
      }

      public function NextID($generatorID)
      {
             return @ibase_gen_id($generatorID,1);
      }

      public function Close() 
      {
             if($this->result) 
                @ibase_free_result($this->result);
                
            $this->myEof = false;                                      
            $this->rows=-1;
            $this->rowActual=-1;
            $this->result=0;
      }

}
