<?php
class Loader
{
    /**
     * 自动加载类
     * @param $class 类名
     */
    public static function autoload($class)
    {
        $path = '';
        $path = str_replace('_', '/', $class) . '.php';
        include_once($path);
    }
}