<?php

class Client
{

    /**
     * @var array
     */
    protected $conf = ['host' => '','ip' => '','port' => ''];

    /**
     * @var Encryptor
     */
    public $cryptor = null;

    /**
     * @var swoole_client
     */
    protected $cli = null;

    /**
     * @var swoole_server
     */
    protected $serv = null;

    /**
     * @var SplQueue
     */
    protected $queue = null;

    /**
     * @var boolean
     */
    protected $lock = true;

    /**
     * @var boolean
     */
    protected $status = false;

    /**
     * @var boolean
     */
    protected $reconn = false;

    /**
     * @var int
     */
    protected $fd = null;

    private static $instance = array();

    /**
     * 创建对象
     * @param int $fd
     * @param swoole_server $serv
     * @param Encryptor $cryptor
     * @return self
     */
    public static function getInstance($fd, swoole_server $serv = null, $cryptor = null)
    {
        if (! isset(self::$instance[$fd])) {
            self::$instance[$fd] = new self($fd, $serv, $cryptor);
        }
        return self::$instance[$fd];
    }

    /**
     * 清除对象
     * @param int $fd
     */
    public static function remove($fd)
    {
        if (isset(self::$instance[$fd])) {
            unset(self::$instance[$fd]);
        }
    }

    public function __construct($fd, swoole_server $serv, $cryptor)
    {
        $this->serv = $serv;
        $this->fd = $fd;
        $this->cryptor = $cryptor;
        $this->queue = new SplQueue();
    }

    public function onConnect(swoole_client $cli)
    {
        Trace::debug("*********cli {$this->fd} connect");
        $this->lock = false;
        $this->send();
    }

    public function onReceive(swoole_client $cli, $data)
    {
        Trace::debug("*********cli {$this->fd} receive  lenght:" . strlen($data) . ".");
        false !== $this->serv->connection_info($this->fd) && $this->serv->send($this->fd, $this->cryptor->encrypt($data));
        $this->lock = false;
        $this->send();
    }

    public function onClose(swoole_client $cli)
    {
        Trace::debug("*********cli {$this->fd} close");
        $this->reconn = true;
    }

    public function onError(swoole_client $cli)
    {
        Trace::debug("*********cli {$this->fd} error");
        $this->serv->close($this->fd);
        $cli->close();
    }

    public function send($data = null)
    {
        //锁定状态写入队列
        if (! empty($data)) {
            $this->queue->push($data);
        }
        if ($this->reconn) {
            Trace::debug("*********cli {$this->fd} reconn \n");
            $this->cli->connect($this->conf['ip'], $this->conf['port']);
            $this->reconn = false;
            $this->lock = true;
        }
        if ($this->queue->isEmpty()) {
            $this->lock = false;
        } elseif (! $this->lock) {
            $this->lock = true;
            $data = $this->queue->shift();
            Trace::debug("*********cli $this->fd send " . strlen($data) . "\n==================\n" . substr($data, 0, 50) . "...\n==============");
            Trace::info(sprintf("Host: %-25s %s", $this->conf['host'], strstr($data, "\n", true))); //;'Host:' . $this->conf['host'] . strstr($data, "\n", true)
            $this->cli->send($data);
        }
    }

    public function init($host)
    {
        $this->conf['host'] = $host;
        $this->status = true;
        $this->cli = $cli = new swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
        $cli->on('connect', [$this,'onConnect']);
        $cli->on('receive', [$this,'onReceive']);
        $cli->on('close', [$this,'onClose']);
        $cli->on('error', [$this,'onError']);
    }

    public function connect($ip, $port)
    {
        $this->conf['ip'] = is_int($ip) ? long2ip($ip) : $ip;
        $this->conf['port'] = $port;
        $this->cli->connect($this->conf['ip'], $this->conf['port']);
    }

    /**
     * 是否被初始化过
     * @return boolean
     */
    public function hasInit()
    {
        return $this->status;
    }

    public function __destruct()
    {
        Trace::debug("*********cli $this->fd __destruct");
        if (isset($this->cli)) {
            $this->cli->isConnected() && $this->cli->close();
            unset($this->cli);
        }
        unset($this->queue);
    }
}