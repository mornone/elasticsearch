<?php

class Base
{
    /**
     * 远程发送请求方法
     * @param $url
     * @param string $type
     * @return mixed
     */
    public function curlFileGetContents($url, $type = 'get')
    {
        $this_header = ["content-type: application/x-www-form-urlencoded; charset=UTF-8"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        switch ($type) {
            case "get" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "post":
                curl_setopt($ch, CURLOPT_POST, true);
//                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "put" :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
//                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "delete":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
//                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    /**
     * 获取请求IP
     */
    public function getIP() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        }
        elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');

        }
        elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        }
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 返回json统一接口
     * @param $ret_data
     * @return string
     */
    public function result($ret_data)
    {
        header("content-type:text/json; charset=utf8");
        $ret = array(
            'code' => $ret_data['code'],
            'message' => isset($ret_data['message']) ? $ret_data['message'] : '请检查参数格式',
        );
        if (isset($ret_data['data']))
            $ret['data'] = $ret_data['data'];
        return json_encode($ret);
    }
}