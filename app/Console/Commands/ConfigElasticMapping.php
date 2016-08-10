<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Extensions\ElasticClient;

class ConfigElasticMapping extends Command{
	
	protected $signature = 'stylewe:config-elastic-mapping {index : option:pageview,event} {--force : 强制更新，会删除现有的数据}';
	
	protected $description = 'set collector mapping';
	
	public function __construct(){
		parent::__construct();
	}
	
	public function handle(){
		$force = $this->option('force');
		$index = $this->argument('index');
		
		switch($index){
			case 'pageview':
				ElasticClient::getInstance()->configPageviewMapping($force);
				break;
			case 'event':
				ElasticClient::getInstance()->configEventMapping($force);
				break;
		}
		
	}
}