<?php

require_once "taulaBD.php";

class invalidOrder extends Exception {};

class filtreTaulaBD
{
      public  $where, $fields, $numRows, $camps;
      private $query;
      private $taula;
      private $joins, $orderBy;
       
      static $instance = false;
     
      private $numJoin;
      
      public function __construct(taulaBD $taula)
      {
             $this->query = Null;
             $this->taula = $taula;
             $this->joins = "";
             $this->fields = array();
             $this->numRows = $this->numJoin = 0;
      }

      public function __get($property)
      {
              $property = strtoupper($property);
              if (isset($this->camps[$property]))
                 return $this->camps[$property];
              else
                 return false;
      }

      public function __set($property, $value)
      {
             $property = strtoupper($property);
             $this->camps[$property] = $value;
      }

      public function addField($fieldName)
      {
             if(!in_array($fieldName, $this->fields)) 
                array_push($this->fields, $fieldName);
      }
       
      public function leftJoin(taulaBD $taulaJoin, $campJoinSrc, $campJoinDst)
      {
             $this->joins .= "left join {$taulaJoin->nomTaula} on {$taulaJoin->nomTaula}.$campJoinSrc = $campJoinDst ";
      }

      public function orderBy($campsOrdres, $tipus="asc")
      {
             if (!(($tipus == "asc") || ($tipus == "desc")))
                throw new invalidOrder("el tipus d'ordre es invalid");
 
             $this->orderBy = "order by ".$campsOrdres." ".$tipus;        
      }

      private function allibera()
      {
             if($this->query!=Null)
             {
                $this->query->tanca_consulta();
                unset($this->query);
             }
      }

      final public function get($id="")
      {
            if ($this->query==Null)
            {
                $fieldsStr = "*";
                if ($this->where) $where = "where ".$this->where;
                if (count($this->fields)>0) 
                    $fieldsStr = implode(",",$this->fields);

                $where = "";
                if ($id) 
                    $where .=" ".$this->taula->condPrimaryKey($id);

                $qStr = "select $fieldsStr from {$this->taula->nomTaula} ".$this->joins." ".$where." ".$this->orderBy;
                $this->query = new consulta_sql($this->taula->getConnection());
                $this->query->fer_consulta($qStr, $assoc=true);  
            }
           
            $final = $this->query->Eof();

            $this->numRows = $this->query->LastRecord();
            $this->camps = array();

            if (!$final)
            { 
                $this->camps =  array_map(function ($value) { return utf8_encode($value); }, $this->query->row); 
                $this->query->Skip();
                if ($id) $this->allibera();
            } else
                $this->allibera();
            
            return(!$final); 
      }

      public function clean()
      {
             $this->allibera();
             $this->fields = array();
             $this->where = "";
             $this->orderBy = "";
             $this->numRows = 0;
      }

      public function getInstance($parent)
      {
             if(!filtreTaulaBD::$instance)
                 filtreTaulaBD::$instance = new filtreTaulaBD($parent);

             return filtreTaulaBD::$instance;
      }
}
