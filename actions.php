<?php

class actions
{
    public static function execute()
    {
        Header('X-Accel-Buffering: no');// nginx-1.5.6 及其以上版本支持

        $config = App::getConfig();

        self::checkRateLimit($config['site']['rateLimit']);

        $commands = $config['site']['commands'];

        $param = $_GET + $_POST;
        $host = isset($param['host']) ? $param['host'] : '';
        $cmd  = isset($param['cmd']) ? $param['cmd'] : '';

        if (stripos($host, 'localhost') !== FALSE)
        {
            echo "<script>parent.alert('请输入正确的IP地址或域名');</script>";
            echo '<script>parent.req_complete()</script>';
            exit;
        }

        if (isset($commands[$cmd]))
        {
            if ($cmd == "ping6" || $cmd == "traceroute6")
            {
                $result = dns_get_record($host, DNS_AAAA);
                if (!empty($result))
                {
                    $ip = $result[0]["ipv6"];
                }
                else
                {
                    $ip = "";
                }
            }
            else
            {
                $ip = gethostbyname($host);
            }

            if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE || $ip == '127.0.0.1')
            {
                echo "<script>parent.alert('请输入正确的IP地址或域名$ip');</script>";
                echo '<script>parent.req_complete()</script>';
                exit;
            }

            call_user_func(array(__CLASS__, $cmd), $ip, $commands[$cmd]);

            echo '<script>parent.req_complete()</script>';
        }
        else
        {
            echo "<script>parent.alert('无效命令');</script>";
            echo '<script>parent.req_complete()</script>';
            exit;
        }
    }

    private static function checkRateLimit($rateLimit)
    {
        if (isset($rateLimit['enable']) && $rateLimit['enable'])
        {
            $option = [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'ua' => $_SERVER['HTTP_USER_AGENT']
            ];
            $rateLimitClass = 'ratelimit\\' . $rateLimit['provider']['class'];

            if (empty($rateLimit['minute']))
            {
                $rateLimit['minute'] = 100; //每分钟限制100次
            }

            /**
             * @var RateLimitInterface $rateLimitInterface
             */
            $rateLimitInterface = new $rateLimitClass($option);
            if ($rateLimitInterface->allow($rateLimit['minute']) === FALSE)
            {
                exit('<script>parent.alert("操作太频繁了")</script>');
            }
        }
    }

    protected static function host($host, $cmd)
    {
        $args = [
            'cmd' => $cmd,
            'ip'  => $host,
        ];

        echo '<script>parent.update_start()</script>';
        ob_flush();
        flush();

        $response = command\host::execute($args);
        $response = trim($response);
        echo "<script>parent.update_view('{$response}')</script>";
        ob_flush();
        flush();
    }

    protected static function ping($host, $cmd)
    {
        $args = [
            'cmd' => $cmd,
            'ip'  => $host,
        ];

        echo '<script>parent.update_start()</script>';
        ob_flush();
        flush();

        command\ping::execute($args, function($line){
            echo '<script>parent.update_view("' . trim($line) . '")</script>';
            ob_flush();
            flush();
        });
    }

    protected static function traceroute($host, $cmd)
    {
        $args = [
            'cmd' => $cmd,
            'ip'  => $host,
        ];

        echo '<script>parent.update_start()</script>';
        ob_flush();
        flush();

        command\traceroute::execute($args, function($line, $json) {
            echo '<script>parent.update_view("' . trim($line) . '", ' . json_encode($json) . ')</script>';
            ob_flush();
            flush();
        });
    }

    protected static function ping6($host, $cmd)
    {
        $args = [
            'cmd' => $cmd,
            'ip'  => $host,
        ];

        echo '<script>parent.update_start()</script>';
        ob_flush();
        flush();

        command\ping::execute($args, function($line){
            echo '<script>parent.update_view("' . trim($line) . '")</script>';
            ob_flush();
            flush();
        });
    }

    protected static function traceroute6($host, $cmd)
    {
        $args = [
            'cmd' => $cmd,
            'ip'  => $host,
        ];

        echo '<script>parent.update_start()</script>';
        ob_flush();
        flush();

        command\traceroute::execute($args, function($line, $json) {
            echo '<script>parent.update_view("' . trim($line) . '", ' . json_encode($json) . ')</script>';
            ob_flush();
            flush();
        });
    }
}
