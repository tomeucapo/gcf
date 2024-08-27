<?php
namespace gcf;

use ErrorException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Message\AMQPMessage;

class MQRPCClient
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $callback_queue;
    private ?string $response = null;
    private mixed $corr_id;
    private string $queueName;
    private int $numberRetries = 0;
    public function __construct(string $host, int $port, string $user, string $pass, string $queueName)
    {
        $this->queueName = $queueName;

        $this->connection = new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $pass
        );

        $this->CreateConsumer();
    }
    private function CreateConsumer() : void
    {
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );

        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            true,
            false,
            false,
            array(
                $this,
                'onResponse'
            )
        );
    }

    public function onResponse($rep) : void
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    /**
     * Metode que ens permet cridar a una funció remota mitjançant una coa
     * @param $data
     * @return string
     * @throws ErrorException
     */
    public function call($data) : string
    {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string) $data,
            [
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            ]
        );

        try {
            $this->channel->basic_publish($msg, '', $this->queueName);
            while (!$this->response)
            {
                $this->channel->wait(null, false, 3000);
            }
        } catch (AMQPChannelClosedException $ex) {
            // If channel fails, try to create new channel

            if ($this->numberRetries < 4)
            {
                error_log($ex->getMessage(). " Retry call number {$this->numberRetries}");
                $this->CreateConsumer();
                $this->call($data);
                $this->numberRetries++;
            } else {
                error_log("Number retries exceeded, aborting operation!");
                $this->numberRetries = 0;
                throw $ex;
            }
        }

        return $this->response;
    }
}