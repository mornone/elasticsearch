<?php
ini_set('display_errors', 1);

//加载Log4php类库
include_once(dirname(__FILE__).'/log4php/Logger.php');
//初始化配置
Logger::configure('log4php.xml');
//获取日志类
$logger = Logger::getLogger('test1');
//写入日志
$logger->info("info日志内容");
$logger->error("error日志内容");
$logger->debug("debug日志内容");