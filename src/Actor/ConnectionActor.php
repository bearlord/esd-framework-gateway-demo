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

    protected $heartbeated = false;

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


    public function proxyForward($data)
    {
        $nodes = $this->getNodes();
        $designatedNode = $this->designatedNode();
        $client = new ServiceClient([
            'serviceName' => 'TcpDataService',
            'nodes' => $nodes,
            'node' => $designatedNode,
            'protocol' => Protocol::PROTOCOL_JSON_RPC,
        ]);

        $res = $client->request("process", [
            'fd' => $this->data['fd'],
            'clientData' => $data,
            'remoteIp' => $this->data['remote_ip'],
            'remotePort' => $this->data['remote_port'],

        ]);
        $response = sprintf("gateway print rpc result: %s\n", $res);
        Server::$instance->send($this->data['fd'], $response);

        if (!$this->heartbeated) {
            $this->initHeartBeat();
            $this->heartbeated = true;
        }
    }

    public function initHeartBeat()
    {
        //10秒一个心跳包
        $this->tick(10 * 1000, function (){
            $this->heartBeatCallback('heartbeart');
        });
    }

    protected function heartBeatCallback($data)
    {
        Server::$instance->getLog()->critical("heartbeat");
        Server::$instance->send($this->data['fd'], $data);
    }

    /**
     * @param ActorMessage $message
     * @return mixed|void
     */
    protected function handleMessage(ActorMessage $message)
    {
        $this->proxyForward($message->getData());
    }
}