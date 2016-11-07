<?php
ini_set('display_errors', 1);

defined("APP_ROOT") or define("APP_ROOT", dirname(dirname(__FILE__)));
defined("LIBRARY_PATH") or define('LIBRARY_PATH', APP_ROOT . "/library");
set_include_path(LIBRARY_PATH . PATH_SEPARATOR . get_include_path());
//require APP_ROOT.'/log4php/Logger.php';
require APP_ROOT.'/autoload.php';
require APP_ROOT.'/vendor/autoload.php';
spl_autoload_register(array('Loader', 'autoload'));

$_GET = ['appid' => 'mornone', 'token' => 'mornone'];
$elsearch = new ElSearch();
$elsearch->reBuild();