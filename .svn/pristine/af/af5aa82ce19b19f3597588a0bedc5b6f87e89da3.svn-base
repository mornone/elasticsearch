<?php
require 'vendor/autoload.php';
$hosts = ['127.0.0.1:9200'];
$serializer = '\Elasticsearch\Serializers\SmartSerializer';
$clientBuilder = Elasticsearch\ClientBuilder::create();   // Instantiate a new ClientBuilder
//$clientBuilder->setHosts($hosts);           // Set the hosts
//$clientBuilder->setSerializer($serializer);
$client = $clientBuilder->build();          // Build the client object
if ($client) {
    echo 'Elasticsearch has connected';
}
exit;
//索引数据
$params_index_arr = [
    [
        'index' => 'ch_index',
        'type' => 'ch_type',
        'zt_main_id' => '34',
        'zt_is_show' => '1',
        'zt_type' => '1',
        'zt_body' => [
            'keywords' => '滋鱼烤鱼加盟'
        ],
    ],
    [
        'index' => 'ch_index',
        'type' => 'ch_type',
        'zt_main_id' => '35',
        'zt_is_show' => '1',
        'zt_type' => '1',
        'zt_body' => [
            'keywords' => '今磨房五谷养生'
        ],
    ],
    [
        'index' => 'ch_index',
        'type' => 'ch_type',
        'zt_main_id' => '36',
        'zt_is_show' => '1',
        'zt_type' => '1',
        'zt_body' => [
            'keywords' => '卡乐滋美式快餐'
        ],
    ]
];
//print_r($params_index_arr);
//exit;


for($i = 0; $i < 10; $i++) {
    $params['body'][] = [
        'index' => [
            '_index' => 'ch_index',
            '_type' => 'ch_type',
        ]
    ];

    $params['body'][] = [
        'main_id' => '34',
        'keywords' => '滋鱼烤鱼加盟',
        'type' => '1',
        'is_show' => '1',
    ];
}
//print_r($params);
//exit;
//$responses = $client->bulk($params);
//print_r($responses);
//exit;

//foreach($params_index_arr as $params_index){
////    $response = $client->index($params_index);
////    print_r($response);
//}



$params_get = [
    'index' => 'ch_index',
    'type' => 'ch_type',
    'id' => 'ch_id',
];
//$client->delete($params_get);
//$response = $client->get($params_get);
//print_r($response);
//exit;


$params_search = [
    'index' => 'ch_index',
    'type' => 'ch_type',
    'body' => [
        'query' => [
            'match' => [
                'keywords' => '加盟'
            ]
        ]
    ]
];
/*$response = $client->search($params_search);
$match_data = $response['hits']['hits'];
foreach($match_data as $v){
    $params_tmp = [
        'index' => $v['_index'],
        'type' => $v['_type'],
        'id' => $v['_id']
    ];
    $client->delete($params_tmp);
}*/
$response = $client->search($params_search);
//print_r($response);
//exit;
$params_del = [
    'index' => 'ch_index',
    'type' => 'ch_type',
];
