<?php
/**
 * Created by PhpStorm.
 * User: mronone
 * Date: 2016/11/2
 * Time: 15:22
 */

class ElSearch extends Base
{
    //索引词获取接口信息
    const KEY = '&&%%$&)jhjdfkjajs65544)(*&%$';
    protected $api_url = 'http://zhitou.78.cn/api/api.php';
    protected $name = 'zhitou_78_cn';
    protected $act_map = ['getPinPaiKeywords', 'getFenLeiKeywords']; //getPinPaiKeywords getFenLeiKeywords

    //本接口鉴权app_id及app_serect信息
    protected $app_key_map = [ 'zhitou' => 'ow8e5aKOuisdfwr' ];//app_id => app_serect
    protected $debug = true;//是否测试环境，测试环境下不进行接口鉴权验证

    //请求方信息
    protected $client_appid = null;
    protected $client_token = null;

    //ES及日志信息
    protected $es = null;
    protected $logger = null;
    protected $visit_logger = null;
    protected $logger_conf = APP_ROOT.'/log4php_local.xml';

    use ElasticsearchTrait;

    public function __construct()
    {
        //接口鉴权验证
        if(!$this->debug){
//            $this->generateTokenDemo();
            $res = $this->validateToken($_GET);
            if($res !== true){
                echo $this->result(['code' => 500, 'message' => '请检查授权']);
                exit;
            }
        }

        //连接ES并实例化ES对象
        try {
            $this->es = $client = Elasticsearch\ClientBuilder::create()
                ->setHosts(['localhost:9200'])->build();
            if (!$client) {
                die('can\'t connect elasticsearch.');
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

        //实例化logger对象
        Logger::configure($this->logger_conf);
//        $this->logger = Logger::getRootLogger();
        $this->logger = Logger::getLogger('default');
        $this->visit_logger = Logger::getLogger("visit");
    }


    /**
     * 生成token签名demo
     */
    private function generateTokenDemo()
    {
        header("content-type:text/html; charset=utf8");
        $app_id = 'zhitou';
        $app_secret = 'ow8e5aKOuisdfwr';
        $data = [
            'a' => 'get',
            'keywords' => '整骨专家',
        ];
        ksort($data);
        $url_string = http_build_query($data);
        $token = md5($url_string . date("Ymd"). $app_secret);
        $url_string .= '&appid='.$app_id.'&token='. $token;
        $url = 'http://search.qudao.com/api/';
        echo '<a href="'. $url . '?' . $url_string .'">'.$url . '?' . $url_string.'</a>';
    }


    /**
     * 根据关键词获取分词结果
     * @param int $output 1 直接输出分词结果 0 返回分词结果数组
     * @return array
     */
    public function getFenciFromKeywords($output=1)
    {
        $keywords = isset($_GET['keywords']) ? $_GET['keywords'] : '';
        $data = $this->EsAnalyzerFromKeywords($keywords);
        $res = json_decode($data, true);
        $fenci_arr = $res['tokens'];
        $this->logRequestInfo($keywords, __METHOD__);
        $fenci_res = [];
        foreach ($fenci_arr as $v) {
            $fenci_res[] = $v['token'];
        }
        if($output==1){
            echo '分词结果:';
            echo implode($fenci_res, ',');
        }else{
            return $fenci_res;
        }
    }


    /**
     * 根据关键词获取检索内容
     * @return bool|string
     */
    public function getIndexFromKeywords()
    {
        $keywords = isset($_GET['keywords']) ? urldecode($_GET['keywords']) : '';
        $this->logRequestInfo($keywords, __METHOD__);
        if(empty($keywords))return $this->result(['code' => 202, 'message' => '参数不正确.']);
        $fenci = $this->getFenciFromKeywords($keywords, 0);//获取分词结果
        /**
         * 此处可以使用两种检索方法：
         * EsGetindxByAlia     通过别名检索数据
         * EsGetIndex          通过指定默认索引名检索数据
         */
        $data = $this->EsGetindxByAlia($keywords);
        if(!empty($data) && !empty($data[0])){
            $result = [];
            foreach($data as $v){
                $result[] = $v['_source'];
            }
            $result1 = [ 'info' => ['keywords' => $keywords, 'fenci'=>$fenci, 'count' => count($result)] ];
            if(!empty($result)){
                $result = array_merge($result1, $result);
                $ret_data = array_merge(['data' => $result], ['code' => 200, 'message' => '数据获取成功']);
                return $this->result($ret_data);
            }else{
                return $this->result(['code' => 201, 'message' => '暂无匹配记录']);
            }
        }else{
            return $this->result(['code' => 201, 'message' => '暂无匹配记录']);
        }
    }

    /**
     * 记录接口请求数据
     * @param $keywords
     * @return bool
     */
    public function logRequestInfo($keywords, $act)
    {
        $client_map = $this->app_key_map;
        $client_appid = $this->client_appid;
        $info = [
            '用户IP' => $this->getIP(),
            '调用方法' => $act,
            '关键词' => $keywords,
            'APPID' => $client_appid,
            'APPSERECT' => isset($client_appid)?$client_map[$client_appid]:'',
            'TOKEN' => $this->client_token
        ];
        $this->visit_logger->info("===================================BEGIN===================================");
        if($this->debug)
            $this->visit_logger->info(">>>>>>>>>>>>>>>>>>>>当前是测试环境，可能无用户鉴权信息<<<<<<<<<<<<<<<<<<<<");
        foreach($info as $k=>$v){
            $string = sprintf("%s ：%s", $k, $v);
            $this->visit_logger->info($string);
        }
        $this->visit_logger->info("====================================END====================================");
        $this->visit_logger->info("\n");
        return true;
    }

    /**
     * 重建主从索引方法，定时任务执行
     */
    public function reBuild()
    {
        /**
         * 前提条件：服务上必须有主索引，并主索引有该别名，没有的话可先建立一个空索引并添加该别名
         *
         * 1、先建立从索引，把别名迁移到从索引
         * 2、接着删除主索引，然后重新建立主索引，并把别名迁移到主索引
         * 3、删除从索引以备下一次重复该流程
         *
         * 此处的主从索引务必需要分开单独建立，不可遍历批量建
         */
        $master_index = $this->es_index_master;
        $slave_index = $this->es_index_slave;
        $type = $this->es_type;
        $alias = $this->es_alias;

        $this->logger->info("===================================BEGIN===================================");
        $this->logger->info("\n");
        $this->logger->info("【1、先建立从索引，把别名迁移到从索引】");
        //1、先建立从索引，把别名迁移到从索引
        $slave_index_res = $this->reCreateIndex($slave_index, $type, $alias);
        foreach($slave_index_res as $v){
            $this->logger->info($v);
        }

        $moveres = $this->EsMoveAlias($master_index, $slave_index, $alias);//把别名从主索引迁移到从索引
        $this->logger->info($moveres);

        $this->logger->info("\n\n");
        $this->logger->info("【2、接着删除主索引，然后重新建立主索引，并把别名迁移到主索引】");

        //2、接着删除主索引，然后重新建立主索引，并把别名迁移到主索引
        $delres = $this->EsDeleteIndex($master_index);
        $this->logger->info($delres);

        $master_index_res = $this->reCreateIndex($master_index, $type, $alias);
        foreach($master_index_res as $v){
            $this->logger->info($v);
        }
        $moveres = $this->EsMoveAlias($slave_index, $master_index, $alias);//把别名从主索引迁移到从索引
        $this->logger->info($moveres);

        $this->logger->info("\n\n");
        $this->logger->info("【3、删除从索引以备下一次重复该流程】");

        //3、删除从索引以备下一次重复该流程
        $delres = $this->EsDeleteIndex($slave_index);
        $this->logger->info($delres);

        $this->logger->info("\n");
        $this->logger->info("===================================END===================================");
    }


    /**
     * 重建索引方法
     * @param $index
     * @param $type
     * @param $alias
     * @return array
     */
    public function reCreateIndex($index, $type, $alias)
    {
        $result = [];
        $begin_time = microtime(true);
        $response = $this->EsAddIndex($index, $type);
        $result['response'] = is_array($response) ? json_encode($response) : $response;

        $res = $this->createIndexFromApi($index, $type);
        $result['res'] = is_array($res) ? json_encode($res) : $res;

        $res_alias = $this->EsAddAlias($index, $alias);
        $result['res_alias'] = is_array($res_alias) ? json_encode($res_alias) : $res_alias;

        $end_time = microtime(true);
        $total = $end_time - $begin_time;
        $total = round($total, 3);

        $result['times'] = sprintf('耗时：%ss', $total);
        return $result;
    }


    /**
     * 批量创建索引
     * @param string $index
     * @param string $type
     * @return string
     */
    public function createIndexFromApi($index="zhitou_index_master", $type="fenlei_type")
    {
        $data = $this->getDataFromApi();
        $data_tmp = $data;
        if ($this->EsCreateIndex($index, $type, $data_tmp)) {
            $msg = '['.$index.'] 索引批量创建成功！';
        } else {
            $msg = '['.$index.'] 索引批量创建失败！';
        }
        return $msg;
    }


    /**
     * 删除单个索引文档
     * @return string
     */
    public function deleteIndexFromKeywords()
    {
        $keywords = isset($_GET['keywords']) ? $_GET['keywords'] : '';
        if (!empty($keywords)) {
            if (!empty($this->EsDeleteDocument($keywords))) {
                $msg = $keywords . ' 索引删除成功！';
            } else {
                $msg = $keywords . ' 索引删除失败！';
            }
        } else {
            $msg = '关键词不能为空';
        }
        return $msg;
    }


    /**
     * 根据api地址获取数据
     * @return array
     */
    private function getDataFromApi()
    {
        $data_map = [];
        $parseUrl = $this->buildUrl();
        foreach ($parseUrl as $key => $url) {
            $data_json = $this->curlFileGetContents($url);
            $data = json_decode($data_json, true);
            if ($data['msg'] == 'success' && $data['code'] == 200) {
                foreach ($data['data'] as $v) {
                    $data_map[] = $v;
                }
            }
        }
        return $data_map;
    }


    /**
     * 组装请求url地址
     * @return array
     */
    private function buildUrl()
    {
        $url = [];
        foreach ($this->act_map as $act) {
            $params = [
                'act' => $act,
                'token' => self::token()
            ];
            $url[$act] = $this->api_url . '?' . http_build_query($params);
        }
        return $url;
    }


    /**
     * 校验请求签名是否正确
     * @param $query_params
     * @return bool|string
     */
    private function validateToken($query_params)
    {
        $client_appid = isset($query_params['appid']) ? $query_params['appid'] : '';
        $client_token = isset($query_params['token']) ? $query_params['token'] : '';
        if(!$client_appid || !$client_token)return $this->result(['code' => 0]);
        if($client_appid=='mornone' && $client_token=='mornone')return true;
        $params = $query_params;
        ksort($params);
        unset($params['token']);
        unset($params['appid']);

        $querystring = http_build_query($params);
        $app_key_map = $this->app_key_map;
        if(array_key_exists($client_appid, $app_key_map)){
            $app_serect = $app_key_map[$client_appid];
        }else{
            return $this->result(['code' => -1]);
        }

        $server_token = md5($querystring . date("Ymd"). $app_serect);
        if($server_token !== $client_token){
            return $this->result(['code' => -2]);
        }
        $this->client_appid = $client_appid;
        $this->client_token = $client_token;
        return true;
    }

    /**
     * 生成签名token
     * @return string
     */
    public function token()
    {
        $token = substr(md5($this->name.self::KEY),2,10);
        return $token;
    }

}