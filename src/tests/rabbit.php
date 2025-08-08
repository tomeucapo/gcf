<?php

use gcf\MQRPCClient;
use Laminas\Json\Json;

require_once "../init.php";

$workload = [
    "operation" => 0,
    "type" => 0,
    "albara" => [
        "idAlbara"    => 1000,
        "data"        => "01/01/2020",
        "codCli"      => "0004",
        "codObra"     => "AF08B008",
        "centreCost"  => "AF08B008",
        "centreCost1" => "B",
        "centreCost2" => "08",
        "esCompra"    => false,
        "linies"      => [["codArt" => "31010002", "quantitat" => 5, "preu" => 30.2]]
    ]
];

$client = new MQRPCClient("afexvm4.afexservicios.net", 5672, "personal", "P3rs0n4l.", "A3_SERVICE_QUEUE");
$response = $client->call(Json::encode($workload));
print_r($response);

/*
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();


$channel->queue_declare('albarans', false, false, false, false);

$data = [
	"idAlbara"    => 1000,
	"data"        => "01/01/2020",
	"codCli"      => "0004",
	"codObra"     => "AF08B008",
	"centreCost"  => "AF08B008",
	"centreCost1" => "B",
	"centreCost2" => "08",
	"esCompra"    => false,
	"linies"      => [["codArt" => "31010002", "quantitat" => 5, "preu" => 30.2]]
];

$msg = new AMQPMessage(
    json_encode($data)
);

$channel->basic_publish($msg, '', 'albarans');

echo ' [x] Sent \n';

$channel->close();
$connection->close();
*/
