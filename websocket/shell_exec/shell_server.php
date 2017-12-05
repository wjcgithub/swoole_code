<?php
/**
 * Created by PhpStorm.
 * User: evolution
 * Date: 17-12-5
 * Time: 下午8:35
 */
class ShellServer
{
    const DATABASE_DSN = 'mysql:host=localhost;dbname=test';
    const USERNAME = 'root';
    const PWD = 'brave';
    const WSHOST = 'localhost';
    const WSPORT = '12345';

    private $server;
    private $process;
    private $async_process = [];

    public function __construct()
    {
        $this->server = new swoole_websocket_server(self::WSHOST, self::WSPORT);
        $this->server->set([
            'worker_num'=>2,
            'dispatch_mode'=>2,
            'daemonize'=>0
        ]);

        $this->server->on('message', [$this, 'onMessage']);
        //说明当前服务可以支持http的请求
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('workerstart', [$this, 'onWorkerStart']);

        //重定向输入输出，并开启管道
        $this->process = new swoole_process([$this, 'onProcess'], true);
        $this->server->addProcess($this->process);
        $this->server->start();
    }

    public function onWorkerStart(swoole_server $server, $worker_id)
    {
        swoole_process::signal(SIGCHLD, function ($sig){
            //必须为false，非阻塞模式
            while ($ret = swoole_process::wait(false)){
                echo "PID={$ret['pid']}". PHP_EOL;
            }
        });
    }

    public function onMessage(swoole_server $server, swoole_websocket_frame $frame)
    {
        var_dump($frame);
        $data = json_decode($frame->data, true);
        var_dump($data);
        $cmd = $data['cmd'];

        $is_block = isset($data['is_block']) ? $data['is_block'] : 0;
        if ($is_block) {
            //需要阻塞等待，有交互效果的命令　　gdb, top
            if (isset($this->async_process[$frame->fd])){
                $process = $this->async_process[$frame->fd];
            } else {
                $process = new swoole_process([$this, 'onTmpProcess'], true, 2);
                $process->start();
                $this->async_process[$frame->fd] = $process;
                //监听输出
                swoole_event_add($process->pipe, function () use ($process, $frame){
                    $data = $process->read();
                    var_dump($data);
                    $this->server->push($frame->fd, $data);
                });
            }

            $process->write($cmd);
            sleep(1);
        } else {
            //直接执行的命令 ls,  tree
            $this->process->write($cmd);
            $data = $this->process->read();
            //将数据传递回客户端
            $this->server->push($frame->fd, $data);
        }
    }

    public function onTmpProcess(swoole_process $worker)
    {
        //读取命令
        $cmd = $worker->read();
        //打开执行管道
        $handle = popen($cmd, 'r');

        swoole_event_add($worker->pipe, function() use ($worker, $handle, $cmd){
           if ($cmd == 'exit') {
               $worker->exit();
           }
           //执行命令
           fwrite($handle, $cmd);
        });

        //读取命令的执行结果从handler句柄中，并echo到管道中返回
        while (!feof($handle)){
            $buffer = fread($handle, 18192);
            echo $buffer;
        }
    }

    public function onProcess(swoole_process $worker)
    {
        while (1){

        }
    }
}

new ShellServer();