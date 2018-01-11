<?php 

class PM
{
    const PID_FILE = 'pid.json';

    private static $storage;

    private function __construct(){}
        
    private static function init()
    {
        if(empty(self::$storage) and file_exists(self::PID_FILE)) {
            $json = file_get_contents(self::PID_FILE);
            foreach(json_decode($json, true) as $process) {
                if(isPortAlive($process['port'])) self::$storage[] = $process;
            }
        } else {
            self::$storage = [];
        }
    }

    public static function push(array $data)
    {
        self::init();
        self::$storage[] = $data;
    }

    public static function pop($port)
    {
        self::init();
        $storage = [];
        $ret = null;
        foreach(self::$storage as $process) {
            if($process['port'] != $port) {
                $storage[] = $process;
            } else {
                $ret = $process;
            }
        }
        self::$storage = $storage;
        return $ret;
    }

    public static function has($port)
    {
        self::init();
        foreach(self::$storage as $process) {
            if($process['port'] == $port) return true;
        }
        return false;
    }

    public static function kill($port)
    {
        self::init();
        $storage = [];
        foreach(self::$storage as $process) {
            if($process['port'] == $port) {
                exec("taskkill /PID {$process['pid']} /T /F");
            } else {
                $storage[] = $process;
            }
        }
        self::$storage = $storage;
        return self::has($port);
    }

    public static function fetchJob()
    {
        self::init();
        return self::$storage;
    }

    public static function save()
    {
        $fp = fopen(self::PID_FILE, 'w');
        fwrite($fp, json_encode(self::$storage));
        fclose($fp);
    }
}


$params = $_GET;
$action = $params['action'];

if(function_exists($action)) {
    return jsonResponse($action($params));
} else {
    return jsonResponse(['code' => 'fail', 'data' => "{$action} not exists"]);
}





/* functions */
function jsonResponse($mixed)
{
    if(is_array($mixed)) {
        $mixed['time'] = time();
        echo json_encode($mixed);
    } else {
        echo json_encode(['code' => 'success', 'data' => $mixed, 'time' => time()]);
    }
}

function isPortAlive($port)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'localhost');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PORT, $port);
    $res = curl_exec($ch);
    return ($res == false) ? false : true;
}

function fetchJob()
{
    return ['code' => 'success', 'data' => PM::fetchJob()];
}

function startJob($params)
{
    $cmd = urldecode($params['cmd']);
    $port = $params['port'];

    if(PM::has($port) and isPortAlive($port)) return ['code' => 'fail', 'data' => "{$cmd}:{$port} is alive"];
    PM::pop($port);

    popen("start {$cmd}", 'r');
    $st = time();
    $now = $st;
    while($now - $st < 15) {
        $now = time();
        if(isPortAlive($port)) {
            $process = [
                'cmd' => $cmd,
                'name' => strtolower($cmd), 
                'pid' => getPidByPort($port),
                'port' => $port,
            ];
            PM::push($process);
            PM::save();
            return ['code' => 'success', 'data' => $process];
        } else {
            sleep(1);
        }
    }
    return ['code' => 'fail', 'data' => 'fail to start a job'];
}

function stopJob($params)
{
    $port = $params['port'];
    $res = PM::kill($port);
    PM::save();

    if(!$res) {
        return ['code' => 'fail', 'data' => 'Fail to stop job'];
    } else {
        return ['code' => 'success', 'data' => 'Job Stopped'];
    }
}

function checkJob($params)
{
    $status = isPortAlive($params['port']);
    
    return ['code' => 'success', 'data' => ['status' => $status]];
}

function getPidByPort($port)
{
    $p = popen('netstat -ano -p TCP', 'r');
    while(false !== $row = fgets($p, 1024)) {
        if(strpos($row, 'TCP') === false) continue;
        $row = preg_replace('/\s+/', ',', trim($row));
        list(,$line,,,$pid) = explode(',', $row);
        list(,$match_port) = explode(':', $line);
        if($match_port == $port) return $pid;
    }
    return false;
}








?>