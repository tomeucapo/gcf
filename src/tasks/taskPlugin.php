<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 1/23/2018
 * Time: 3:25 PM
 */

namespace gcf\tasks;

use Exception;
use gcf\Environment;
use stdClass;

abstract class taskPlugin
{
    const DEFAULT_TASK_TIMEOUT = 30000;
    const DEFAULT_PING_TIMEOUT = 300;

    protected string $jobHandle;
    protected $jobID;

    protected TaskPriority $priority;

    protected TaskExecutionMode $mode;

    private int $expireResult = 1000;

    public $jobResult;

    /**
     * @var array
     */
    protected array $servers = [];

    /**
     * @var \stdClass
     */
    protected \stdClass $payload;

    /**
     * taskPlugin constructor.
     * @param array $servers
     */

    public function __construct(array $servers)
    {
        $this->servers = $servers;
        $this->mode = TaskExecutionMode::BACKGROUND;
        $this->priority = TaskPriority::NORMAL;
        $this->payload = new \stdClass();
    }

    private static function TaskKey($taskName, $jobHandle) : string
    {
        return "TASK:$taskName:$jobHandle";
    }

    public function setPriority(TaskPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function setMode(TaskExecutionMode $mode): void
    {
        $this->mode = $mode;
    }

    abstract protected function status($jobClientId);
    abstract public function ClientStatus();

    abstract function execute($taskName, $payload=null);

    /**
     * Task dispacher method. This method can call any task with your name and store results into cache
     * @param string $taskName
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public function __call(string $taskName, array $args = [])
    {
        if (empty($args))
           $jobId = $this->execute($taskName);
        else $jobId = $this->execute($taskName, $args[0]);

        if ($this->mode === TaskExecutionMode::BACKGROUND)
        {
            $cache = Environment::getInstance()->appCfg->getCache();

            $jobStatus = new \stdClass();
            $jobStatus->handle = $this->jobHandle;
            $jobStatus->timestamp = time();
            $jobStatus->status = "STARTED";
            $jobStatus->result = null;
            $cache->set(self::taskKey($taskName, $jobId), $jobStatus, $this->expireResult);
        }

        return $jobId;
    }

    /**
     * Get job status
     * @param string $taskName
     * @param $jobHandle
     * @return StdClass
     * @throws Exception
     */
    public function getStatus(string $taskName, $jobHandle) : \stdClass
    {
        $cache = Environment::getInstance()->appCfg->getCache();

        $taskKey = self::TaskKey($taskName, $jobHandle);
        $jobStatusInfo = $cache->get($taskKey);

        if (empty($jobStatusInfo))
            throw new \Exception("Job ID $taskKey not found!");

        $jobStatus = $this->status($jobHandle);

        $jobStatusInfo->timestamp = time();
        if ($jobStatusInfo->status !== $jobStatus["status"])
            $jobStatusInfo->status = $jobStatus["status"];

        $cache->set(self::TaskKey($taskName, $jobHandle), $jobStatusInfo, $this->expireResult);

        return $jobStatusInfo;
    }

    /**
     * Get job results
     * @param $taskName
     * @param $jobHandle
     * @return mixed
     * @throws errorTaskResponse
     * @throws Exception
     */
    public function getResults($taskName, $jobHandle) : mixed
    {
        if (!$this->jobResult)
        {
            $cache = Environment::getInstance()->appCfg->getCache();
            $jobStatusInfo = $cache->get(self::TaskKey($taskName, $jobHandle));

            if (empty($jobStatusInfo))
                return null;

            if ($jobStatusInfo->result !== null)
                $this->jobResult = $jobStatusInfo->result;
        }

        $dataResult = @json_decode($this->jobResult);
        $errNo = json_last_error();
        if ($errNo != JSON_ERROR_NONE)
            throw new errorTaskResponse($errNo);

        return $dataResult;
    }

}
