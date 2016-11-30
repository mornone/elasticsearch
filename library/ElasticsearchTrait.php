<?php
trait ElasticsearchTrait
{
    protected $es_host = '';//localhost:9200
    protected $es_index_master = '';//my_zhitou_index_master
    protected $es_index_slave = '';//my_zhitou_index_slave
    protected $es_type = '';//fenlei_type
    protected $es_alias = '';//别名 my_zhitou_index_alias
    protected $es_request_url = '';
    protected $es = null;

    /*================================以下为封装ES API接口方法=====================================================*/
    /**
     * 创建ES对象
     * @return \Elasticsearch\Client|null
     */
    public function createEsObj($es_host, $es_index_master, $es_index_slave, $es_type, $es_alias)
    {
        $this->es_host = $es_host;
        $this->es_index_master = $es_index_master;
        $this->es_index_slave = $es_index_slave;
        $this->es_type = $es_type;
        $this->es_alias = $es_alias;
        //连接ES并实例化ES对象
        try {
            $client = null;
            $this->es = $client = Elasticsearch\ClientBuilder::create()->setHosts([$this->es_host])->build();
            if (!$client) {
                die('can\'t connect elasticsearch.');
            }

            $this->es_request_url =  'http://'.$this->es_host.'/';
            return $client;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

    }

    /**
     * 添加索引并设置映射类型
     * @param $index
     * @param $type
     * @return array
     */
    private function EsAddIndex($index, $type)
    {
        $result = [];
        $params = [
            'index' => $index,
            'body' => [
                'settings' => [
                    'index' => [
                        'number_of_replicas' => 0,
                        'number_of_shards' => 5,
                        'refresh_interval' => '5s'//建索引会有延迟，30S之后再进来，针对大数据，小量数据无所谓了
                    ]
                ]
            ]
        ];
        try{
            $response_index = $this->es->indices()->create($params);
        }catch (Exception $e){
            $response_index = $e->getMessage();
        }
        $result['index'] = $response_index;

        $params_mapping = [
            'index' => $index,
            'type' => $type,
            'body' => [
                $this->es_type => [
                    'properties' => [
                        'main_id' => [
                            'type' => 'integer'
                        ],
                        'is_show' => [
                            'type' => 'integer'
                        ],
                        'type' => [
                            'type' => 'integer'
                        ],
                        'keywords' => [
                            'type' => 'string',
                            'index' => 'analyzed',
                            'analyzer' => 'ik',
                        ],
                    ]
                ]
            ]
        ];
        try{
            $response_mapping = $this->es->indices()->putMapping($params_mapping);
        }catch (Exception $e){
            $response_mapping = $e->getMessage();
        }
        $result['mapping'] = $response_mapping;
        return $result;
    }


    /**
     * 根据索引名称删除索引
     * @param $name
     */
    public function EsDelIndex($name)
    {
        if(!empty($name)){
            $params = [
                'index' => $name
            ];
            try{
                $response = $this->es->indices()->delete($params);
            }catch (Exception $e){
                $response = $e->getMessage();
            }
            $res = is_array($response) ? $response : json_decode($response, true);
            print_r($res);
            exit;
        }
    }


    /**
     * 为索引设置别名
     * @param $index_name
     * @param $alias_name
     * @return array|mixed|string
     */
    private function EsAddAlias($index_name, $alias_name)
    {
        $params = [
            'index' => $index_name,
            'name' => $alias_name,
            'body' => [
                'filter' => [
                    'term' => [
                        'is_show' => '1'
                    ]
                ]
            ]
        ];
        try{
            $response = $this->es->indices()->putAlias($params);
        }catch (Exception $e){
            $response = $e->getMessage();
        }
        $result = is_array($response) ? $response : json_decode($response, true);
        return $result;
    }


    /**
     * 索引别名进行主从（从主）索引切换
     * @param $masterIndex
     * @param $slaveIndex
     * @param $alias_name
     * @return bool
     */
    private function EsMoveAlias($masterIndex, $slaveIndex, $alias_name)
    {
        $params = [
            'index' => $masterIndex,
            'name' => $alias_name
        ];
        $this->es->indices()->deleteAlias($params);
        $this->EsAddAlias($slaveIndex, $alias_name);
        return "索引别名 [{$alias_name}] 从 [{$masterIndex}] 迁移到 [{$slaveIndex}]";
    }


    /**
     * 批量创建索引
     * @param $index
     * @param $type
     * @param $data
     * @return bool
     */
    private function EsCreateIndex($index, $type, $data)
    {
        $params = [];
        $num = count($data);
        for ($i = 0; $i < $num; $i++) {
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_type' => $type,
                ]
            ];

            $params['body'][] = [
                'main_id' => $data[$i]['main_id'],
                'keywords' => $data[$i]['keywords'],
                'is_show' => $data[$i]['is_show'],
                'type' => $data[$i]['type'],
            ];
        }
        $responses = $this->es->bulk($params);
        if ($responses) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 根据关键通过索引别名来获取数据
     * @param $keyword
     * @return bool
     */
    public function EsGetindxByAlia($keyword)
    {
        $url = $this->es_request_url . $this->es_alias.'/_search/?size=5000&q=keywords:'.urlencode($keyword);
        $data = json_decode($this->curlFileGetContents($url), true);
        $total = $data['hits']['total'];
        $result = $data['hits']['hits'];
        if (!empty($result)) {
            return $result;
        }else{
            return [''];
        }
    }


    /**
     * 查找索引
     * @param $keyword
     * @return mixed
     */
    private function EsGetIndex($keyword)
    {
        $params_search = [
            'index' => $this->es_index_master,
            'type' => $this->es_type,
            'body' => [
                //带过滤条件查询
                "query" => [
                    "filtered" => [
                        "filter" => ["wildcard" => ["is_show" => "1"]],
                        "query" => ["match" => ["keywords" => $keyword]]
                    ]
                ]

                //不带过滤条件全部查询
                /*'query' => [
                    'match' => [
                        'keywords' => $keyword
                    ]
                ]*/
            ]
        ];

        //分词查询
        /*$params_search_analyzer = [
            'index' => $this->es_index_master,
            'type' => $this->es_type,
            'analyzer' => 'ik',
            'analyze_wildcard' => true,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['term' => ['keywords' => $keyword]],
                            ['wildcard' => ['is_show' => '1']]
                        ]
                    ]
                ]
            ]
        ];*/

        $response = $this->es->search($params_search);
        $match_data = $response['hits']['hits'];
        return $match_data;
    }

    /**
     * @param $keyword
     * @return bool
     */
    private function EsDeleteDocument($keyword)
    {
        //批量删除实现的逻辑是先根据词查出数据，然后遍历删除
        $params_search = [
            'index' => $this->es_index_master,
            'type' => $this->es_type,
            'body' => [
                'query' => [
                    'match' => [
                        'keywords' => $keyword
                    ]
                ]
            ]
        ];

        $response = $this->es->search($params_search);
        $match_data = $response['hits']['hits'];
        foreach ($match_data as $v) {
            $params_tmp = [
                'index' => $v['_index'],
                'type' => $v['_type'],
                'id' => $v['_id']
            ];
            try {
                if ($this->es->delete($params_tmp)) {
                    return true;
                }
            } catch (Exception $e) {
                print $e->getMessage();
                exit();
            }
        }
        return false;
    }

    /**
     * 根据索引名删除索引
     * @param $index
     * @return mixed|string
     */
    private function EsDeleteIndex($index)
    {
        $url = $this->es_request_url . $index.'/';
        $res = $this->curlFileGetContents($url, 'delete');
        $data = json_decode($res, true);
        if (isset($data['acknowledged']) && $data['acknowledged'] == 'true') {
            return "索引： [{$index}] 删除成功";
        }else{
            return 'failed';
        }
    }

    /**
     * 根据关键词分词
     * @param $keywords
     * @return mixed
     */
    private function EsAnalyzerFromKeywords($keywords)
    {
        $keywords = urlencode($keywords);
        $url = $this->es_request_url . $this->es_index_master .'/_analyze?field=keywords&analyzer=ik&text=' . $keywords;
        $res = $this->curlFileGetContents($url);
        return $res;
    }
}