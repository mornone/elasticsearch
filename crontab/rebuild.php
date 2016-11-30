<?php
ini_set('display_errors', 1);
date_default_timezone_set("Asia/Shanghai");

defined("APP_ROOT") or define("APP_ROOT", dirname(dirname(__FILE__)));
defined("LIBRARY_PATH") or define('LIBRARY_PATH', APP_ROOT . "/library");
set_include_path(LIBRARY_PATH . PATH_SEPARATOR . get_include_path());
require APP_ROOT.'/autoload.php';
require APP_ROOT.'/vendor/autoload.php';
spl_autoload_register(array('Loader', 'autoload'));

$_GET = ['appid' => 'mornone', 'token' => 'mornone'];
$es_config['idx_host'] = '127.0.0.1:9200';//es服务地址
$es_config['idx_master'] = 'my_zhitou_index_master';//es主索引名称
$es_config['idx_slave'] = 'my_zhitou_index_slave';//es从索引名称
$es_config['idx_type'] = 'fenlei_type';//索引type
$es_config['idx_alias'] = 'my_zhitou_index_alias';//索引别名
$elsearch = new ElSearch($es_config);
$elsearch->reBuild();