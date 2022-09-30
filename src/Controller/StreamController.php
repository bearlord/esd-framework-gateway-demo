<?php


namespace App\Controller;


use App\Actor\ConnectionActor;
use ESD\Core\Plugins\Event\Event;
use ESD\Plugins\Actor\Actor;
use ESD\Plugins\Actor\ActorMessage;
use ESD\Plugins\Cloud\Gateway\Annotation\RestGatewayController;
use ESD\Plugins\Cloud\Gateway\Controller\GatewayController;
use ESD\Server\Coroutine\Server;
use ESD\Go\GoController;
use ESD\Plugins\Cloud\Gateway\Annotation\RequestMapping;
use ESD\Plugins\Cloud\Gateway\Annotation\TcpController;
use ESD\Plugins\Pack\ClientData;
use ESD\Plugins\Pack\GetBoostSend;
use ESD\Plugins\Redis\GetRedis;
use ESD\Yii\Yii;

/**
 * @TcpController(portNames={"tcp"})
 * Class StreamController
 * @package App\Controller
 */
class StreamController extends GatewayController
{

    use GetBoostSend;
    use GetRedis;


    /**
     * @RequestMapping("onConnect")
     * @return void
     * @throws \Exception
     */
    public function actionOnConnect()
    {
        Server::$instance->getLog()->critical("on Connect!");
    }

    /**
     * @RequestMapping("onClose")
     * @return void
     * @throws \Exception
     */
    public function actionOnClose()
    {
        Server::$instance->getLog()->critical("on Close!");
    }


    /**
     * @RequestMapping("onReceive")
     * @throws \ESD\Plugins\Redis\RedisException
     */
    public function actionOnTcpReceive()
    {
        printf("=====\n");
        $fd = $this->clientData->getFd();
        $data = $this->clientData->getData();
        var_dump($data);

        try {
            $connectionActor = Actor::getProxy("connection-" . $fd, false);

            if (!empty($connectionActor)) {
                $connectionActor->setData([
                    'fd' => $fd,
                    'last_communication_time' => time(),
                    'remote_ip' => $this->clientData->getClientInfo()->getRemoteIp(),
                    'remote_port' => $this->clientData->getClientInfo()->getRemotePort()
                ]);
            }
        } catch (\Exception $exception) {
            $connectionActor = Actor::create(ConnectionActor::class, "connection-" . $fd, [
                'fd' => $fd,
                'last_communication_time' => time(),
                'remote_ip' => $this->clientData->getClientInfo()->getRemoteIp(),
                'remote_port' => $this->clientData->getClientInfo()->getRemotePort()
            ]);
        }

        $actorMessage = new ActorMessage($data, date("YmdHis").  mt_rand(10000, 99999));
        $connectionActor->sendMessage($actorMessage);


        return true;
    }
}