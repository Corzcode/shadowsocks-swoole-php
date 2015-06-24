# shadowsocks-swoole-php

须要swoole扩展
------
https://github.com/swoole/swoole-src

启动方式
------
```
php ss-server.php
```
守护运行
```
php ss-server.php -d
```

配置文件
------
config.php
```
return [
    'port' => 8388,
    'passwd' => '123456',
    'method' => 'aes-256-cfb'
];
```

其它
------
加密类与sock5头部参考
https://github.com/walkor/shadowsocks-php