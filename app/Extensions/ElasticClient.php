<?php
namespace App\Extensions;

use Elasticsearch;
use Illuminate\Support\Facades\Log;

class ElasticClient
{

    const INDEX_TYPE_NAME = 'data';

    private $esClient = null;

    private static $instance = null;

    private function __construct()
    {
        $this->esClient = Elasticsearch\ClientBuilder::create()->setHosts([
            env('ELASTIC_HOST', '127.0.0.1:9200')
        ])->build();
    }

    public static function getInstance()
    {
        if (! static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /*
     * Save Operation
     */
    public function save(array $data)
    {
        //if timezone is not UTC.the data in es will put in wrong index 
        date_default_timezone_set('UTC');
        $date = (new \DateTime())->setTimestamp($data['timestamp'])->format('Y.m.d');
        $index = $this->getIndex($date);
        $this->checkIndex($index);
        
        $result = $this->esClient->index([
            'index' => $index,
            'type' => self::INDEX_TYPE_NAME,
            'body' => $data
        ]);
        return $result;
    }
    
    /**
     * Simple Search Wrap
     * @param array $esQuery
     */
    public function search(array $query){
        $index = $this->getIndex('*');
        return $this->esClient->search([
            'index' => $index,
            'type' => self::INDEX_TYPE_NAME,
            'body' => $query,
        ]);
    }

    private $_index_mapping = [];
    public function checkIndex($index){
        if(!array_get($this->_index_mapping,$index)){
            $this->updateMapping($index, [
                'properties' => static::$commonMapping,
                "dynamic_templates" => [
                    [
                        "strings" => [
                            "match_mapping_type" => "string",
                            "mapping" => [
                                "type" => "keyword"
                            ]
                        ]
                    ]
                ]
            ]);
            $this->_index_mapping[$index] = true;
        }
    }
    /*
     * config mapping
     */
    public function updateMapping($index, array $mapping, $force = false)
    {
        $cr_mapping = $this->esClient->indices()->getMapping();
        
        $updateMapping = false;
        // 实际的更新索引，因为ES限制，所以要先删除数据，再重建索引和数据，否则会遇到merge mapping exception
        if (isset($cr_mapping[$index]) && $force) {
            $this->esClient->indices()->delete([
                'index' => $index
            ]);
            $cr_mapping = false;
        }
        // 创建索引
        if (! isset($cr_mapping[$index])) {
            $this->esClient->indices()->create([
                'index' => $index,
                'body' => [
                        "number_of_shards" => 3,
                        "number_of_replicas" => 0
                ]
            ]);
            $updateMapping = true;
        }
        
        // 创建mapping
        if ($updateMapping) {
            echo "update mapping...\n";
            $params = [];
            $params['index'] = $index;
            $params['type'] = self::INDEX_TYPE_NAME;
            
            $params['body']['data'] = $mapping;
            
            $this->esClient->indices()->putMapping($params);
        }
    }

    /*
     * common method
     */
    protected function getIndex($suffix)
    {
        return env('ELASTIC_INDEX', 'collector') . '-' . $suffix;
    }
    
    private static $commonMapping = [
        'timestamp' => [
            'type' => 'date',
            'format' => 'epoch_second'
        ],
        'uuid' => [
            'type' => 'keyword',
        ],
        'ip' => [
            'type' => 'keyword',
        ],
        'os' => [
            'type' => 'keyword',
        ],
        'os_version' => [
            'type' => 'keyword',
        ],
        'device' => [
            'type' => 'keyword',
        ],
        'client_type' => [
            'type' => 'keyword',
        ],
        'client_name' => [
            'type' => 'keyword',
        ],
        'client_version' => [
            'type' => 'keyword',
        ],
        'user_agent' => [
            'type' => 'keyword',
        ],
        'user_id'   => [
            'type' => 'keyword',
        ],
        'type'  => [
            'type' => 'keyword',
        ],
        'profile_id' => [
            'type' => 'keyword',
        ],
        'profile_name' => [
            'type' => 'keyword',
        ],
        'country_code' => [
            'type' => 'keyword',
        ],
    
    
        //Pageview Extra
        'referer' => [
            'type' => 'keyword',
        ],
        'url' => [
            'type' => 'keyword',
        ],
        'utm_source' => [
            'type' => 'keyword',
        ],
        'utm_medium' => [
            'type' => 'keyword',
        ],
        'utm_term' => [
            'type' => 'keyword',
        ],
        'utm_content' => [
            'type' => 'keyword',
        ],
        'utm_campaign' => [
            'type' => 'keyword',
        ],
        
        //Event Extra
        'event' => [
            'properties' => [
                'category' => [
                    'type' => 'keyword',
                ],
                'action' => [
                    'type' => 'keyword',
                ],
                'label' => [
                    'type' => 'keyword',
                ],
                'value' => [
                    'type' => 'keyword',
                ],
                'value_number' => [
                    'type' => 'double'
                ]
            ]
        ],
        'experiments' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'keyword',
                ],
                'variation' => [
                    'type' => 'integer'
                ]
            ]
        ]
        
    ];
}