<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Extensions\ElasticClient;

class ConfigElasticMapping extends Command{
	
	protected $signature = 'stylewe:config-elastic-mapping 
		{index : option:pageview,event} 
		{--date= : 创建某一天的索引，默认创建第二天的索引}
		{--force : 强制更新，会删除现有的数据}';
	
	protected $description = 'set collector mapping';
	
	public function __construct(){
		parent::__construct();
	}
	
	public function handle(){
		$force = $this->option('force');
		$index = $this->argument('index');
		$date = $this->option('date');
		if(!$date){
			$date = new \DateTime();
			$date->modify('+1 day');
			$date = $date->format('Y.m.d');
		}
		
		switch($index){
			case 'pageview':
				ElasticClient::getInstance()->configPageviewMapping($date,$force);
				break;
			case 'event':
				ElasticClient::getInstance()->configEventMapping($date,$force);
				break;
		}
		
	}
}