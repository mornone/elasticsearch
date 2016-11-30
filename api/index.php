<?php
ini_set('display_errors', 1);
date_default_timezone_set("Asia/Shanghai");

defined("APP_ROOT") or define("APP_ROOT", dirname(dirname(__FILE__)));
defined("LIBRARY_PATH") or define('LIBRARY_PATH', APP_ROOT . "/library");
defined("LOG_CONFIG") or define('LOG_CONFIG', APP_ROOT . "/log4php_local.xml");
set_include_path(LIBRARY_PATH . PATH_SEPARATOR . get_include_path());
require APP_ROOT.'/autoload.php';
require APP_ROOT.'/vendor/autoload.php';
spl_autoload_register(array('Loader', 'autoload'));

$es_config['idx_host'] = '127.0.0.1:9200';//es服务地址
$es_config['idx_master'] = 'my_zhitou_index_master';//es主索引名称
$es_config['idx_slave'] = 'my_zhitou_index_slave';//es从索引名称
$es_config['idx_type'] = 'fenlei_type';//索引type
$es_config['idx_alias'] = 'my_zhitou_index_alias';//索引别名
$elsearch = new ElSearch($es_config);

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