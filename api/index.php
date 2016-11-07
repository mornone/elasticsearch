<?php
ini_set('display_errors', 1);

defined("APP_ROOT") or define("APP_ROOT", dirname(dirname(__FILE__)));
defined("LIBRARY_PATH") or define('LIBRARY_PATH', APP_ROOT . "/library");
defined("LOG4PHP_PATH") or define('LOG4PHP_PATH', APP_ROOT . "/log4php");
set_include_path(LIBRARY_PATH . PATH_SEPARATOR . PATH_SEPARATOR . get_include_path());
require APP_ROOT.'/autoload.php';
require APP_ROOT.'/vendor/autoload.php';
spl_autoload_register(array('Loader', 'autoload'));

$elsearch = new ElSearch();
$a = isset($_GET['a']) ? $_GET['a'] : '';
$action_map = [
    'get'       => 'getIndexFromKeywords',//根据关键词获取检索内容

    //'deldoc'  => 'EsDeleteIndex',
];

if(array_key_exists($a, $action_map)){
    $action = $action_map[$a];
    $msg = $elsearch->$action();
    echo $msg;
}else{
    echo '参数不正确.';
    exit();
}