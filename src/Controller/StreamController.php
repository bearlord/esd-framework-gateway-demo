<?php


namespace App\Controller;


use App\Actor\ConnectionActor;
use App\Libs\ExtraHelper;
use App\Model\Iccid;
use App\Model\Stream\StreamModel;
use ESD\Core\Plugins\Event\Event;
use ESD\Plugins\Actor\Actor;
use ESD\Server\Coroutine\Server;
use ESD\Go\GoController;
use ESD\Plugins\EasyRoute\Annotation\RequestMapping;
use ESD\Plugins\EasyRoute\Annotation\TcpController;
use ESD\Plugins\Pack\ClientData;
use ESD\Plugins\Pack\GetBoostSend;
use ESD\Plugins\Redis\GetRedis;
use ESD\Yii\Yii;

/**
 * @TcpController(portNames={"tcp"})
 * Class StreamController
 * @package App\Controller
 */
class StreamController extends GoController
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
        $fd = $this->clientData->getFd();
        $data = $this->clientData->getData();

//        var_dump($fd, $data);

        try {
            $connectionActor = Actor::getProxy("connection-" . $fd, true);

            if (!empty($connectionActor)) {
                $connectionActor->setData([
                    'fd' => $fd,
                    'client_data' => $data,
                    'last_communication_id' => time(),
                    'remote_ip' => $this->clientData->getClientInfo()->getRemoteIp(),
                    'remote_port' => $this->clientData->getClientInfo()->getRemotePort()
                ]);
            }
        } catch (\Exception $exception) {
            $connectionActor = Actor::create(ConnectionActor::class, "connection-" . $fd, [
                'fd' => $fd,
                'client_data' => $data,
                'last_communication_id' => time(),
                'remote_ip' => $this->clientData->getClientInfo()->getRemoteIp(),
                'remote_port' => $this->clientData->getClientInfo()->getRemotePort()
            ]);
        }

        $connectionActor->proxyForward();

        return true;
    }
}