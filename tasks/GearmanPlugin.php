<?php
/**
 * Created by PhpStorm.
 * User: tomeu
 * Date: 1/23/2018
 * Time: 3:26 PM
 */

namespace gcf\tasks;

class GearmanPlugin extends taskPlugin
{
    /**
     * @var \GearmanClient
     */
    private $client;

    /**
     * @var \GearmanTask
     */
    private $actualTask;

    /**
     * GearmanPlugin constructor.
     * @param array $servers
     * @throws \Exception
     * @author errorJobServer
     */
    public function __construct(array $servers)
    {
        if (!class_exists("GearmanClient")) {
            throw new \Exception("El driver GearmanClient no esta instal·lat, no podem executar la tasca");
        }

        parent::__construct($servers);

        $this->client = new \GearmanClient();
        $this->client->addServers($servers[0]);

        if (!$this->client->ping("test"))
            throw new errorJobServer("El servidor {$servers[0]} no contesta");

        $this->client->setCreatedCallback(function (\GearmanTask $task) use (&$handles) {
            $this->jobHandle = $task->jobHandle();
        });
    }

    /**
     * @param $taskName
     * @param null $payload
     * @return string
     * @throws errorExecutingTask
     * @throws \Exception
     */
    public function execute($taskName, $payload = null)
    {
        $this->client->setTimeout(self::DEFAULT_PING_TIMEOUT);
        if (!@$this->client->ping("none"))
            throw new \Exception("La tasca $taskName no es pot executar, el servidor de tasques no contesta!");
        $this->client->setTimeout(self::DEFAULT_TASK_TIMEOUT);

        $mode = "";
        if ($this->mode == self::BACKGROUND_MODE)
            $mode = "Background";
        else if (empty($this->priority))
            $mode = "Normal";

        $this->jobResult = null;
        $this->jobID = uniqid('', true);

        $executionMethod = "addTask{$this->priority}$mode";
        $this->actualTask = $this->client->$executionMethod($taskName, json_encode($payload), $this->jobID);
        if ($this->mode === self::NORMAL_MODE)
        {
            $this->actualTask = $this->client->$executionMethod($taskName, json_encode($payload), $this->jobID);
            $this->client->setDataCallback(function(\GearmanTask $task) {
                $this->jobResult = $task->data();
            });
        }

        $this->client->runTasks();

        switch ($this->client->returnCode())
        {
            case GEARMAN_WORK_DATA:
            case GEARMAN_WORK_STATUS:
            case GEARMAN_SUCCESS: break;
            case GEARMAN_WORK_FAIL:
            default: throw new errorExecutingTask($this->client->error());
        }

        return $this->jobHandle;
    }

    protected function status($jobClientId)
    {
        $jobStatus = $this->client->jobStatus($jobClientId);

        $status = "COMPLETED";
        if ($jobStatus[0] && $jobStatus[1])
            $status = "PROCESSING";
        else if ($jobStatus[0] && !$jobStatus[1])
            $status = "QUEUED";

        $actual = (int)$jobStatus[2];
        $max = (int)$jobStatus[3];

        return ["status" => $status,
                "progress" => ($max > 0) ? $actual*100/$max : 0];
    }

}