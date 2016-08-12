<?php
namespace App\Extensions;

use Elasticsearch;

class ElasticClient{
	
	const INDEX_PAGEVIEW = 'pageview';
	const INDEX_EVENT = 'event';
	const INDEX_ECOMMERICE = 'ecommerice';
	
	const INDEX_TYPE_NAME = 'data';
	
	private static $commonMapping = [
			'timestamp' => [
					'type' => 'date',
					'format' => 'epoch_second'
			],
			'uuid'	=>[
					'type'	=> 'string',
					'index'	=> 'not_analyzed',
			],
			'ip' => [
					'type' => 'ip',
			],
			'os' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
			'os_version' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
			'device' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
			'client_type' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
			'client_name' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
			'client_version' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
			'user_agent' => [
					'type' => 'string',
					'index' => 'not_analyzed',
			],
	];
	
	private $esClient = null;
	
	private static $instance = null;
	
	private function __construct(){
		$this->esClient = Elasticsearch\ClientBuilder::create()
		->setHosts([env('ELASTIC_HOST','127.0.0.1:9200')])
		->build();
	}
	
	
	public static function getInstance(){
		if(!static::$instance){
			static::$instance = new self();
		}
		return static::$instance;
	}
	
	/*
	 * Save Operation
	 */
	
	public function savePageview(array $data){
		$date = (new \DateTime())->format('Y.m.d');
		$this->esClient->index([
				'index'	=> $this->getIndex(self::INDEX_PAGEVIEW,$date),
				'type'	=> self::INDEX_TYPE_NAME,
				'body'	=> $data,
		]);
	}
	
	public function saveEvent(array $data){
		$date = (new \DateTime())->format('Y.m.d');
		$this->esClient->index([
				'index' => $this->getIndex(self::INDEX_EVENT,$date),
				'type'	=> self::INDEX_TYPE_NAME,
				'body'	=> $data,
		]);
	}
	
	/*
	 * config mapping
	 */
	
	public function updateMapping($index,array $mapping,$force=false){
		
		$cr_mapping = $this->esClient->indices()->getMapping();
		
		$updateMapping = false;
		//实际的更新索引，因为ES限制，所以要先删除数据，再重建索引和数据，否则会遇到merge mapping exception
		if(isset($cr_mapping[$index]) && $force){
			$this->esClient->indices()->delete(['index'=>$index]);
			$cr_mapping = false;
		}
		//创建索引
		if(!isset($cr_mapping[$index])){
			$this->esClient->indices()->create(['index'=>$index]);
			$updateMapping = true;
		}
			
		//创建mapping
		if($updateMapping){
			echo "update mapping...\n";
			$params = [];
			$params['index'] = $index;
			$params['type'] = self::INDEX_TYPE_NAME;
		
			$params['body']['data'] = $mapping;
		
			return $this->esClient->indices()->putMapping($params);
		}
	}
	
	public function configPageviewMapping($suffix,$force=false){
		$mapping = array_merge([
				'referer' => [
						'type' => 'string',
						'index' => 'not_analyzed',
				],
				'url' => [
						'type' => 'string',
						'index' => 'not_analyzed',
				],
		],static::$commonMapping);
		
		$mapping = [
				'properties' => $mapping
		];
		
		$this->updateMapping($this->getIndex(self::INDEX_PAGEVIEW,$suffix), $mapping,$force);
	}
	
	public function configEventMapping($suffix,$force=false){
		$mapping = array_merge([
				'category'	=> [
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
				]
		],static::$commonMapping);
		$mapping = [
				'properties' => $mapping
		];
		$this->updateMapping($this->getIndex(self::INDEX_EVENT,$suffix), $mapping,$force);
	}
	
	/*
	 * common method 
	 */
	protected function getIndex($index,$suffix){
		return env('ELASTIC_INDEX','stylewe').'_'.$index.'-'.$suffix;
	}
}