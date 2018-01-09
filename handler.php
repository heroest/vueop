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
            self::$storage = json_decode($json, true);
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
        foreach(self::$storage as $process) {
            if($process['port'] != $port) $storage[] = $process;
        }
        self::$storage = $storage;
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
    return jsonReponse($action($params));
} else {
    return jsonReponse(['code' => 'fail', 'data' => "{$action} not exists"]);
}





/* functions */
function jsonResponse($mixed)
{
    if(is_array($mixed)) {
        echo json_encode($mixed);
    } else {
        echo json_encode(['code' => 'success', 'data' => $mixed]);
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

function startJob($params)
{
    $cmd = $params['cmd'];
    $port = $params['port'];

    if(PM::has($port)) {
        
    }
}

function stopJob($params)
{
    $port = $params['port'];
}

function checkJob($params)
{
    $status = isPortAlive($params['port']);
    return ['code' => 'success', 'data' => $status];
}

function getPidByPort($port)
{

}








?>