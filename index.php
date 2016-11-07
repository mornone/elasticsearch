<?php
require 'vendor/autoload.php';

//1、连接Elasticsearch
$client = Elasticsearch\ClientBuilder::create()->build();

if ($client) {
//    echo 'connected';
}

//索引数据
$params = [
    'index' => 'my_index',
    'type' => 'my_type',
    'id' => 'my_id2',
    'body' => [
        'first field' => 'Adding My First Field In Elasticsearch'
    ],
];
$response = $client->index($params);
echo $response['created'];
print_r($response);
exit;


//获取数据
/*
$params = [
    'index' => 'my_index',
    'type' => 'my_type',
    'id' => 'my_id2',
];

$response = $client->get($params);
echo $response['_source']['first field'];
*/


//根据keyword从Elasticsearch中查询数据
$params = [
    'index' => 'my_index',
    'type' => 'my_type',
    'body' => [
        'query' => [
            'match' => [
                'first field' => 'first'
            ],
        ],
    ],
];

$response = $client->search($params);
print_r($response);
exit;
$hits = count($response['hits']['hits']);
$result = null;
$i = 0;

echo "共返回{$hits}条记录：<br>";

while ($i < $hits) {
    $result[$i] = $response['hits']['hits'][$i]['_source'];
    $i++;
}
foreach ($result as $key => $value) {
    echo $value['first field'] . "<br>";
}

//删除索引
$params = [
    'index' => 'my_index',
    'type' => 'my_type',
    'id' => 'my_id2'
];
//$client->delete($params);