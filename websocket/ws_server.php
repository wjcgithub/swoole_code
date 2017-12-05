<?php

/**
 * Created by PhpStorm.
 * User: evolution
 * Date: 17-12-4
 * Time: 下午7:22
 */
class Server
{
    const DATABASE_DSN = 'mysql:host=localhost;dbname=test';
    const USERNAME = 'root';
    const PWD = 'brave';
    const WSHOST = 'localhost';
    const WSPORT = '12345';

    private $server;
    private $pdo;

    public function __construct()
    {
        global $cfg_table;
        $cfg_table['coc']=['classid','id'];
        $this->server = new swoole_websocket_server(self::WSHOST, self::WSPORT);
        $this->server->set([
            'worker_num' => 8,
            'dispatch_mode' => 2,
            'daemonize' => 0
        ]);

        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('workerstart', [$this, 'onWorkerStart']);
//        $this->server->on('handshake', [$this, 'user_handshake']);
        $this->server->start();
    }

    private function createDb()
    {
        $this->pdo = new PDO(self::DATABASE_DSN, self::USERNAME, self::PWD, [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8;",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ]);
    }


    public function onWorkerStart(swoole_server $server, $worker_id)
    {
        $this->createDb();
        if ($worker_id == 0) {
            $this->server->tick(500, [$this, 'onTick']);
        }
    }

    public function onMessage(swoole_websocket_server $server, $frame)
    {

    }

    /**
     * 当握手协议打开的时候
     */
    public function onOpen()
    {
        global $cfg_table;
        $result = [];
        foreach ($cfg_table as $table => $fields) {
            $result[$table] = $this->select($table, $fields);
        }
        var_dump($result);
        foreach ($this->server->connections as $connection) {
            $this->server->push($connection, json_encode($result));
        }
    }

    public function select($table, $fields)
    {
        $field_list = implode(',', $fields);
        $sql = "select {$field_list} from {$table}";
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            if ($result === false) {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }

        return $result;
    }

    /**
     * 从新向前端发送更新
     */
    private function update()
    {
        $this->onOpen();
    }

    public function onTick()
    {
        $sql = 'select classid from coc limit 1';
        $update = 'update coc set classid=0';
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return;
            }
            if ($result['classid'] != 0) {
                $this->update();
            }
            $statement = $this->pdo->prepare($update);
            $statement->execute();
        } catch (Exception $e) {
            $this->createDb();
        }

    }
}

new Server();