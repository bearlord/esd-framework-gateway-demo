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
        'last_communication_id' => '',
        'client_data' => null,
        'remote_ip' => '',
        'remote_port' => '',
        'server_node' => ''
    ];

    protected function setServerNode($node)
    {
        $this->data['server_node'] = $node;
    }

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

    public function selectNode()
    {
        if (!empty($this->data['server_node'])) {
            return $this->data['server_node'];
        }

        $index = mt_rand(0, 1);
        $node = $this->getNodes()[$index];
        $this->data['server_node'] = $node;
        return $node;
    }


    public function proxyForward()
    {
        $nodes = $this->getNodes();
        $selectNode = $this->selectNode();
        $client = new ServiceClient([
            'serviceName' => 'TcpDataService',
            'nodes' => $nodes,
            'protocol' => Protocol::PROTOCOL_JSON_RPC,
        ]);

        printf("gateway start rpc request\n");
        $client->getClient()->getTransporterInstance()->setNode([
            'schema' => '',
            'host' => $selectNode['host'],
            'port' => $selectNode['port'],
        ]);
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