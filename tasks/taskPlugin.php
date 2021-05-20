<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 1/23/2018
 * Time: 3:25 PM
 */

namespace gcf\tasks;

abstract class taskPlugin
{
    const NORMAL_MODE = 0;
    const BACKGROUND_MODE = 1;

    const NORMAL_PRIO = '';
    const LOW_PRIO = 'Low';
    const HIGH_PRIO = 'High';
    const DEFAULT_TASK_TIMEOUT = 30000;
    const DEFAULT_PING_TIMEOUT = 300;

    protected $jobHandle;
    protected $jobID;
    protected $mode;
    protected $priority;

    private $expireResult = 1000;

    public $jobResult;

    /**
     * @var array
     */
    protected $servers;

    /**
     * @var \stdClass
     */
    protected $payload;

    /**
     * taskPlugin constructor.
     * @param array $servers
     */

    public function __construct(array $servers)
    {
        $this->servers = $servers;
        $this->mode = self::BACKGROUND_MODE;
        $this->payload = new \stdClass();
        $this->priority = self::NORMAL_PRIO;
    }

    private static function TaskKey($taskName, $jobHandle)
    {
        return "TASK:$taskName:$jobHandle";
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function setMode(int $mode)
    {
        $this->mode = $mode;
    }

    abstract protected function status($jobClientId);

    abstract function execute($taskName, $payload=null);

    /**
     * Task dispacher method. This method can call any task with your name and store results into cache
     * @param $taskName
     * @param array $args
     * @return mixed
     * @throws \Zend_Exception
     */
    public function __call($taskName, array $args = [])
    {
        if (empty($args))
           $jobId = $this->execute($taskName);
        else $jobId = $this->execute($taskName, $args[0]);

        if ($this->mode === self::BACKGROUND_MODE)
        {
            $cache = \app\configurador::getInstance()->getCache();

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
     * @param $taskName
     * @param $jobHandle
     * @return array
     * @throws \Zend_Exception
     * @throws \Exception
     */
    public function getStatus($taskName, $jobHandle)
    {
        $cache = \app\configurador::getInstance()->getCache();
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
     * @return mixed|null
     * @throws \Zend_Exception
     * @throws errorTaskResponse
     */
    public function getResults($taskName, $jobHandle)
    {
        if (!$this->jobResult)
        {
            $cache = \app\configurador::getInstance()->getCache();
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

class errorExecutingTask extends \Exception {};
class errorJobServer extends \Exception {};
class errorUnknownJob extends \Exception {};
class errorTaskResponse extends \Exception {};