<?php 

class PM
{
    const PID_FILE = 'pid.json';

    private static $storage = [];

    private function __construct(){}
        
    private static function init()
    {
        if(empty(self::$storage) and file_exists(self::PID_FILE)) {
            $json = file_get_contents(self::PID_FILE);
            foreach(json_decode($json, true) as $process) {
                if(isPortAlive($process['port'])) self::$storage[] = $process;
            }
            // devLog('init:');
            // devLog(self::$storage);
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
                popen("taskkill /PID {$process['pid']} /T /F", 'r');
            } else {
                $storage[] = $process;
            }
        }
        self::$storage = $storage;
        // devLog('after_kill');
        // devLog(self::$storage);
        return !self::has($port);
    }

    public static function fetchJob()
    {
        self::init();
        return self::$storage;
    }

    public static function save()
    {
        // devLog('before_save');
        // devLog(self::$storage);
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
    $p = popen('netstat -ano -p TCP', 'r');
    while(false !== $row = fgets($p, 1024)) {
        if(strpos($row, 'TCP') === false) continue;
        $row = preg_replace('/\s+/', ',', trim($row));
        list(,$line,,,$pid) = explode(',', $row);
        list(,$match_port) = explode(':', $line);
        if(intval($match_port) == intval($port)) return true;
    }
    return false;
}

function fetchJob()
{
    $data = PM::fetchJob();
    PM::save();
    return ['code' => 'success', 'data' => $data];
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
    return ['code' => 'fail', 'data' => 'Fail to start a job'];
}

function stopJob($params)
{
    $res = PM::kill($params['port']);
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


function devLog($msg)
{
    $msg = '[' . date('Y-m-d H:i:s') . '] - ' . json_encode($msg) . "\r\n"; 
    error_log($msg, 3, 'dev.txt');
}





?>