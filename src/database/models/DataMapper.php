<?php
/******************************************************************************
 * taulaBD.php
 *
 * Classe que ens permet treballar amb una taula be base de dades a alt nivell
 * sense necessitat de fer sentencies SQL, per fer les operacions tradicionals
 * de INSERT, DELETE o UPDATE.
 *
 * Tomeu Capó
 ******************************************************************************/

namespace gcf\database\models;

use Exception;
use gcf\database\base_dades;
use gcf\database\consulta_sql;
use gcf\database\drivers\errorQuerySQL;
use gcf\database\errorDriverDB;
use Laminas\Log\Logger;

/**
 * Class taulaBD
 */
abstract class DataMapper
{
    /**
     * Objecte de connexió a bbdd
     * @var base_dades
     */
    protected base_dades $db;

    protected $primaryKey;
    protected $tipusPK;

    protected ?Logger $logger;

    /**
     * @var String
     */
    public string $lastQuery;

    /**
     * @var String
     */
    public string $nomTaula;

    public ?ResultSet $result;

    public $lastError;
    public bool $autoCommit;

    /**
     * @var array
     */
    public array $camps;

    /**
     * @var consulta_sql
     */
    private consulta_sql $commonQuery;

    /**
     * @var string
     */
    protected string $generatorId;

    /**
     * @var boolean
     */
    public bool $useCommonTransact;

    private $commonTransId;

    /**
     * @var DataConverter
     */
    protected $converter;

    /**
     * @param $db base_dades Database connection
     * @param $nomTaula String Mapped table name
     * @param $pk mixed Primary key name or list of fields compound primary key
     * @param $tipusPK mixed Primary key type or types
     * @throws errorDriverDB
     */

    public function __construct(base_dades $db, string $nomTaula, $pk, $tipusPK)
    {
        $this->nomTaula = $nomTaula;
        $this->db = $db;
        $this->primaryKey = $pk;
        $this->tipusPK = $tipusPK;
        $this->camps = [];
        $this->result = null;
        $this->logger = null;
        $this->commonTransId = null;

        $this->useCommonTransact = false;
        $this->autoCommit = false;
        $this->commonQuery = new consulta_sql($this->db);
        $this->converter = new DataConverter($this);
    }


    /***********************************************************************
     * Metodes d'access, propis del mateix PHP5. Ens permeten accedir
     * directament als noms dels camps d'una taula sense emprar el $camps.
     * Simplement fent $taula->nomCamp = valor.
     *
     * @param $property
     * @return mixed|null
     */

    public function __get($property)
    {
        $property = strtoupper($property);
        if (isset($this->camps[$property]))
            return $this->camps[$property];
        return null;
    }

    /**
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        $property = strtoupper($property);
        $this->camps[$property] = $value;
    }

    public function __isset($property)
    {
        $property = strtoupper($property);
        return (isset($this->camps[$property]));
    }

    public function __unset($property)
    {
        $property = strtoupper($property);
        unset($this->camps[$property]);
    }

    /**
     * Metode per buidar els camps de la taula
     */
    public function buidaCamps() : void
    {
        $this->camps = [];
    }

    public function setLogger(Logger $logger) : void
    {
        $this->logger = $logger;
    }

    private function escriuLog(string $msg) : void
    {
        $this->logger?->debug($msg);
    }

    /**
     * Metode que ens monta la condicio del where en cas de que la clau primaria sigui composta
     * @param $ids
     * @return string SQL Condition for a PK
     * @throws noPrimaryKey
     */
    public function condPrimaryKey($ids): string
    {
        $cond = "";
        $i = 0;
        if (is_array($this->primaryKey)) {
            $max = count($this->primaryKey);
            if (count($this->primaryKey) <> count($this->tipusPK))
                throw new noPrimaryKey("No has definit tots els tipus de claus primaries");

            foreach ($this->primaryKey as $camp) {
                $valorPK = $ids[$i];
                if ($this->tipusPK[$i] == 'string')
                    $valorPK = "'$valorPK'";

                $i++;

                if ($i == $max)
                    $cond .= "$camp = $valorPK ";
                else
                    $cond .= "$camp = $valorPK and ";
            }
        } else {
            $valorPK = $ids;
            if ($this->tipusPK == 'string')
                $valorPK = "'$valorPK'";
            $cond = "{$this->primaryKey} = $valorPK";
        }

        return $cond;
    }

    public function PrimaryKey()
    {
        if (is_array($this->primaryKey))
        {
            $pkValue = [];
            foreach ($this->primaryKey as $camp) {
                $pkValue[$camp] = $this->camps[$camp];
            }
            return $pkValue;
        }

        return $this->camps[$this->primaryKey];
    }

    /**
     * @return bool Returns false it fails, true otherwise
     * @throws errorQuerySQL When final SQL statement fails
     * @throws noDataFound When clientType is JSCRIPT and dades_xml is empty
     * @throws errorDriverDB
     */
    public function Nou()
    {
        if ($this->useCommonTransact)
            $cons = $this->commonQuery;
        else $cons = new consulta_sql($this->db);

        $this->converter->Where(null);

        if (empty($this->camps))
            throw new noDataFound("No hi ha dades d'entrada a cap camp de la taula");

        $this->lastQuery = $this->converter->ArrayToSQL(ConverterType::SQLInsert, $this->camps);

        $this->escriuLog($this->lastQuery);

        try {
            $idTrans = null;
            if ($this->autoCommit)
                $idTrans = $cons->iniciTrans();

            $cons->fer_consulta($this->lastQuery);

            if ($this->autoCommit && $idTrans)
            {
                $cons->ferCommit($idTrans);
            }
        } catch (errorQuerySQL $e) {
            $this->escriuLog($e->getMessage());
            throw $e;
        }

        if (!$this->useCommonTransact)
            $cons->tanca_consulta();

        return true;
    }

    /**
     * Modifies an record of model
     * @param mixed $id PK of an register to modify
      * @return bool Returns false it fails, true otherwise
     * @throws Exception
     * @throws errorQuerySQL
     * @throws noDataFound
     */
    public function Modifica($id) : bool
    {
        // Sets where sentence to PK condition
        $this->converter->Where($this->condPrimaryKey($id));

        if (empty($this->camps))
        {
            $this->logger?->debug("[" . __CLASS__ . "::" . __METHOD__ . "] {$this->nomTaula} No hi ha dades d'entrada a cap camp de la taula");
            return false;
        }
        $this->lastQuery = $this->converter->ArrayToSQL(ConverterType::SQLUpdate, $this->camps);

        $this->escriuLog($this->lastQuery);

        $cons = new consulta_sql($this->db);
        try {
            $idTrans = null;
            if ($this->autoCommit)
                $idTrans = $cons->iniciTrans();

            $cons->fer_consulta($this->lastQuery);

            if ($this->autoCommit && $idTrans)
                $cons->ferCommit($idTrans);
        } catch (errorQuerySQL $e) {
            $this->lastError = $e->getMessage();
            $this->escriuLog($e->getMessage());
            throw $e;
        }

        $cons->tanca_consulta();

        return true;
    }

    /**
     * @param $id
     * @param string $fitxerImatge
     * @return bool
     * @throws Exception
     */
    final public function guardaImatge($id, string $fitxerImatge): bool
    {
        $cons = new consulta_sql($this->db);

        if (!$cons->guardaImatge($fitxerImatge)) {
            $this->lastError = "$fitxerImatge not found";
            return false;
        }

        try {
            $this->converter->Where($this->condPrimaryKey($id));
            $this->lastQuery = $this->converter->ArrayToSQL(ConverterType::SQLUpdate, $this->camps);
            $cons->fer_consulta($this->lastQuery);
            $this->escriuLog($this->lastQuery);
            $cons->tanca_consulta();
        } catch (Exception $e) {
            $this->lastError = $cons->lastError();
            $this->escriuLog($e->getMessage());
            throw $e;
        }

        return true;
    }

    /**
     * Carrega un o un conjunt de registres, emprant o bé la PK per cercar o bé una condició.
     * @param string $id Especifica la clau primaria
     * @param string $cond Es un condicional de tipus SQL
     * @param string $orderBy Ordre de la select DESC o ASC
     * @return bool Torna true si ha anat bé o false si no, aquest metode actualment o bé torna true o torna una excepció
     * @throws errorQuerySQL Error de nivell de SQL
     * @throws noDataFound Si no ha trobat cap registre
     * @throws noPrimaryKey Si no ha pogut fer el condicional en base de la PK.
     */
    public function Carrega($id = '', $cond = '', $orderBy = '')
    {
        $cons = new consulta_sql($this->db);

        if (!$cond && $id)
        {
            try {
                $cond = $this->condPrimaryKey($id);
            } catch (noPrimaryKey $e) {
                $this->escriuLog($e->getMessage());
                throw $e;
            }
            $cond = "where " . $cond;
        } else if ($cond)
            $cond = "where " . $cond;

        $query = "select * from {$this->nomTaula} " . $cond . ' ' . $orderBy;

        try {
            if ($this->autoCommit) $cons->ferCommit();
            $cons->fer_consulta($query, $assoc = true);
            $this->camps = [];
        } catch (errorQuerySQL $e) {
            $this->escriuLog($e->getMessage());
            throw $e;
        }

        $this->escriuLog($query);

        if ($cons->Eof())
        {
            $this->escriuLog("Query: $query (return 0 results)");
            throw new noDataFound("La query $query no ha tornat cap registre!");
        }

        $this->camps = $cons->row;

        // Carregam el contigut del BLOB si hi ha algun camp que contengui un blob
        $i = 0;
        foreach ($this->camps as $nomCamp => $valor)
        {
            if ($cons->TipusField($i) === 'BLOB')
            {
               $this->camps[$nomCamp] = !empty($valor) ? $cons->carregarBLOB($valor) : "";
            }
            $i++;
        }

	    $this->lastQuery = $query;
        $this->result = new ResultSet($cons, $this->primaryKey);

        return true;
    }

    /**
     * Get next ID from generator
     * @return mixed
     * @throws Exception
     */
    public function NextId() : mixed
    {
        if (!$this->generatorId)
            throw new Exception("Generator ID not specified!");

        $q = new consulta_sql($this->db);
        $nextId = $q->nextID($this->generatorId);

        if ($nextId === false)
            throw new Exception("Invalid generator $this->generatorId returned value!");

        $q->tanca_consulta();

        return $nextId;
    }

    /**
     * Delete a record from table or delete all records
     * @param $id mixed PK of record
     * @param bool $unRegistre By default, delete only selected record. Otherwise can delete all records if is false.
     * @return bool
     * @throws noPrimaryKey PK definition problems
     * @throws errorQuerySQL
     */
    public function Borra($id, $unRegistre = true)
    {
        $where = '';
        if ($unRegistre) {
            try {
                $where = "where " . $this->condPrimaryKey($id);
            } catch (noPrimaryKey $e) {
                $this->escriuLog($e->getMessage());
                throw $e;
            }
        }

        $idTrans = null;
        $cons = new consulta_sql($this->db);
        if ($this->autoCommit)
            $idTrans = $cons->iniciTrans();

        $queryStr = "delete from {$this->nomTaula} " . $where;
        $cons->fer_consulta($queryStr);
        $this->lastQuery = $queryStr;

        if ($this->autoCommit && $idTrans)
            $cons->ferCommit($idTrans);

        $cons->tanca_consulta();

        return true;
    }

    /**
     * Return a fields serialized to JSON
     * @return string
     */
    public function JSONObject(): string
    {
        return (json_encode($this->camps));
    }

    /**
     * Special method created for extract years of a specific date field and returns array
     * @param string $camp_data Date field for extract years
     * @param string $where SQL condition
     * @return array List of years from this field
     * @throws errorDriverDB
     * @throws errorQuerySQL
     */
    public function ExtreuAnys(string $camp_data, string $where = ""): array
    {
        $llistaAnys = [];

        if ($where)
            $where = "where " . $where;

        $cons = new consulta_sql($this->db);
        $cons->fer_consulta("select extract(year from $camp_data) from {$this->nomTaula} $where group by 1;");

        while (!$cons->Eof())
        {
            $llistaAnys[] = (string)$cons->row[0];
            $cons->Skip();
        }
        $cons->tanca_consulta();

        return $llistaAnys;
    }

    /**
     * Get database connection.
     * @return base_dades
     */
    final public function getConnection(): base_dades
    {
        return $this->db;
    }

    public function commitAll(): bool
    {
        if ($this->useCommonTransact && $this->commonTransId)
        {
            $status = $this->commonQuery->ferCommit($this->commonTransId);
            $this->commonTransId = null;
            return $status;
        }
        return false;
    }

    public function rollbackAll(): bool
    {
        if ($this->useCommonTransact && $this->commonTransId)
        {
            $status = $this->commonQuery->ferRollback($this->commonTransId);
            $this->commonTransId = null;
            return $status;
        }
        return false;
    }

    public function __destruct()
    {
        // Try to commit all transactions
        if ($this->autoCommit && $this->useCommonTransact && $this->commonTransId)
            $this->commonQuery->ferCommit($this->commonTransId);

        // Force to close common query object
        $this->commonQuery->tanca_consulta();
    }
}
