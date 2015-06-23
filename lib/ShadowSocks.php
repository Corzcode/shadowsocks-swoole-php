<?php

/**
 * 代理核心类
 * 
 * @author Corz
 * @namespace package_name
 */
class ShadowSocks
{

    /**
     * setting
     * @var unknown
     */
    protected static $conf = array();

    /**
     * 
     * @var swoole_server
     */
    protected $serv = array();

    /**
     * @var array<swoole_client>
     */
    protected $clients = array();

    /**
     * @var swoole_process
     */
    protected $process = array();

    /**
     * @var Encryptor
     */
    protected $cryptor = null;

    /**
     * @var Encryptor
     */
    protected $querytimes = 0;

    public static function getInstance(array $conf)
    {
        static $instance = null;
        if (! isset($instance)) {
            self::$conf = $conf;
            $instance = new self();
        }
        return $instance;
    }

    public function __construct()
    {
        $ip = empty(self::$conf['ip']) ? '0.0.0.0' : self::$conf['ip'];
        $this->serv = new swoole_server($ip, self::$conf['port'], SWOOLE_PROCESS, SWOOLE_TCP);
        $this->serv->on('connect', [$this,'onConnect']);
        $this->serv->on('receive', [$this,'onReceive']);
        $this->serv->on('close', [$this,'onClose']);
    }

    public function onConnect($serv, $fd)
    {
        echo "Client:Connect $fd.\n";
        Client::getInstance($fd, $this->serv, new Encryptor(self::$conf['passwd'], self::$conf['method']));
    }

    public function onReceive($serv, $fd, $from_id, $rdata)
    {
        $client = Client::getInstance($fd);
        $data = $client->cryptor->decrypt($rdata);
        echo "\n\n\n\n" . str_repeat('#', 20), "\nquerytimes:", ++ $this->querytimes, "\n", str_repeat('#', 20), "\n=======================\nonReceive $from_id : $fd  lenght:" .
             strlen($data) . " content:\n=======================\n" . substr($data, 0, 50) . "...\n=======================\n";
        if (false === $client->hasInit()) {
            echo "hasInit $fd false \n";
            $header = Sock5::parseHeader($data);
            if (! $header) {
                return $serv->close($fd);
            }
            $client->init();
            if (strlen($data) > $header['length']) {
                $data = substr($data, $header['length']);
                $client->send($data);
            }
            swoole_async_dns_lookup($header['addr'], 
                function ($host, $ip) use($header, $client)
                {
                    echo "dnslookup >$fd, $host, $ip \n";
                    $client->connect(ip2long($ip), $header['port']);
                });
        } else {
            echo "hasInit $fd true \n";
            $client->send($data);
        }
    }

    public function onClose($serv, $fd)
    {
        Client::remove($fd);
        echo "Client: $fd Close.\n";
    }

    public function start($setting = array())
    {
        $default = ['timeout' => 1,'poll_thread_num' => 1,'worker_num' => 4,'backlog' => 128,'dispatch_mode' => 2];
        $setting = array_merge($default, $setting);
        $this->serv->set($setting);
        $this->serv->start();
    }
}