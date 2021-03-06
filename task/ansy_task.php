<?php
/**
 * Created by PhpStorm.
 * User: evolution
 * Date: 17-4-6
 * Time: 上午9:58
 */

class Client
{
    private $client;

    public function __construct()
    {
        $this->client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->on('Connect', [$this, 'onConnect']);
        $this->client->on('Receive', [$this, 'onReceive']);
        $this->client->on('Close', [$this, 'onClose']);
        $this->client->on('Error', [$this, 'onError']);
    }

    public function connect()
    {
        $fp = $this->client->connect('127.0.0.1', 9501, 1);
        if (!$fp) {
            echo "Error: {$fp->errMsg} - {$fp->errCode}\n";
            return;
        }
    }

    public function onReceive($cli, $data)
    {
        echo "Get Message From Server: {$data}\n";
    }

    public function onConnect($cli)
    {
        fwrite(STDOUT, "Enter Msg:");
        swoole_event_add(STDIN, function ($fp) {
            global $cli;
            fwrite(STDOUT, "-----Enter Msg:");
            $msg = trim(fgets(STDIN));
            $cli->send($msg);
        });
    }

    public function onClose($cli)
    {
        echo "Client close connection\n";
    }

    public function onError()
    {
    }

    public function send($data)
    {
        $this->client->send($data);
    }

    public function isConnected()
    {
        return $this->client->isConnected();
    }
}

$cli = new Client();
$cli->connect();
