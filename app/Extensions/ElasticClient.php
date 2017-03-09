<?php
namespace App\Extensions;

use Elasticsearch;

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
        $date = (new \DateTime())->setTimestamp($data['timestamp'])->format('Y.m.d');
        $index = $this->getIndex($date);
        $this->checkIndex($index);
        
        $this->esClient->index([
            'index' => $index,
            'type' => self::INDEX_TYPE_NAME,
            'body' => $data
        ]);
    }

    private $_index_mapping = [];
    public function checkIndex($index){
        if(!array_get($this->_index_mapping,$index)){
            $this->updateMapping($index, [
                'properties' => static::$commonMapping 
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
                'index' => $index
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
            
            return $this->esClient->indices()->putMapping($params);
        }
    }

    /*
     * common method
     */
    protected function getIndex($suffix)
    {
        return env('ELASTIC_INDEX', 'stylewe') . '-' . $suffix;
    }
    
    private static $commonMapping = [
        'timestamp' => [
            'type' => 'date',
            'format' => 'epoch_second'
        ],
        'uuid' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'ip' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'os' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'os_version' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'device' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'client_type' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'client_name' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'client_version' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'user_agent' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'user_id'   => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'type'  => [
            'type' => 'string',
            'index' => 'not_analyzed',
        ],
        'profile_id' => [
            'type' => 'string',
            'index' => 'not_analyzed',
        ],
        'profile_name' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'country_code' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
    
    
        //Pageview Extra
        'referer' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'url' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'utm_source' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'utm_medium' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'utm_term' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'utm_content' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'utm_campaign' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
    
        //Event Extra
        'category' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'action' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'label' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'value' => [
            'type' => 'string',
            'index' => 'not_analyzed'
        ],
        'value_number' => [
            'type' => 'double'
        ]
    ];
}