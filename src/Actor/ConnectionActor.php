<?php

namespace App\Actor;

use ESD\Plugins\Actor\Actor;
use ESD\Plugins\Actor\ActorMessage;
use ESD\Plugins\JsonRpc\Client\ServiceClient;
use ESD\Plugins\JsonRpc\Protocol;
use ESD\Plugins\Pack\GetBoostSend;
use ESD\Server\Coroutine\Server;

class ConnectionActor extends Actor
{
    use GetBoostSend;

    protected $data = [
        'fd' => null,
        'server_ip' => '',
        'last_communication_time' => '',
        'client_data' => null,
        'remote_ip' => '',
        'remote_port' => '',
    ];

    protected $serverNode;

    public function getNodes()
    {
        return [
            [
                'schema' => 'tcp',
                'host' => 'localhost',
                'port' => 8086,
            ],
            [
                'schema' => 'tcp',
                'host' => 'localhost',
                'port' => 9086,
            ],
        ];
    }

    public function designatedNode()
    {
        if (!empty($this->serverNode)) {
            return $this->serverNode;
        }

        $node = $this->getNodes()[array_rand($this->getNodes())];
        $this->serverNode = $node;
        return $node;
    }


    public function proxyForward()
    {
        $nodes = $this->getNodes();
        $designatedNode = $this->designatedNode();
        $client = new ServiceClient([
            'serviceName' => 'TcpDataService',
            'nodes' => $nodes,
//            'node' => $designatedNode,
            'protocol' => Protocol::PROTOCOL_JSON_RPC,
        ]);

        printf("gateway start rpc request\n");
        $res = $client->request("process", [
            'fd' => $this->data['fd'],
            'clientData' => $this->data['client_data'],
            'remoteIp' => $this->data['remote_ip'],
            'remotePort' => $this->data['remote_port'],

        ]);
        $response = sprintf("gateway print rpc result: %s\n", $res);
        Server::$instance->send($this->data['fd'], $response);
    }


    protected function handleMessage(ActorMessage $message)
    {
        // TODO: Implement handleMessage() method.
    }
}