<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use App\Extensions\DeviceDetectorRedisCache;
use App\Extensions\ElasticClient;

class CollectionController extends Controller{
	
	const GIF_ONE_PIXEL = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw';
	const COOKIE_KEY = 'stylewe_uuid';
	
	public function pageview(Request $requset){
		if(!($data=$this->getCommonData($requset))){
			return $this->returnFailed();
		}
		$data['referer'] = $requset->query('referer','');
		
		$data['url'] = $requset->query('url',null);
		if(is_null($data['url'])){
			$data['url'] = $requset->header('referer','');
		}
		ElasticClient::getInstance()->savePageview($data);

		return $this->returnImage();
	}
	
	public function ecommerce(Request $request){
		return $this->returnImage();
	}
	
	public function event(Request $request){
		if(!($data=$this->getCommonData($request))){
			return $this->returnFailed();
		}
		$data = array_merge([
			'category' => $request->query('category',''),
			'action' => $request->query('action',''),
			'label' => $request->query('label',''),
			'value' => $request->query('value',''),
			'value_number' => $request->query('value_number',null),
		],$data);
		ElasticClient::getInstance()->saveEvent($data);
		return $this->returnImage();
	}
	
	/**
	 * 每个数据采集，都会有公共信息的
	 * 信息如下return：
	 * 
	 * uuid				浏览器里面的cookie区分唯一浏览器或者手机IMEI
	 * ip				IP
	 * timestamp		发送统计信息的设备IP
	 * user_agent		发送统计信息的设备信息
	 * os				发送统计信息的操作系统
	 * os_version		操作系统版本
	 * device			Desktop ipad
	 * client_type		browser mobile app
	 * client_name		Chrome	Facebook Stylewe-IOS
	 * client_version	客户端版本
	 * @param Request $request
	 */
	protected function getCommonData(Request $request){
		$uuid = $request->cookies->get(self::COOKIE_KEY,null);
		if(is_null($uuid)){
			$uuid = $request->query('stylewe_uuid',null);
		}
		if(is_null($uuid)){
			return false;
		}
		$userAgent = $request->header('User-Agent');
		$ua = $this->parseUserAgent($userAgent);
		return array_merge($ua,[
			'uuid'		=> $uuid,
			'ip'		=> $request->getClientIp(),
			'timestamp'	=> time(),
			'user_agent'	=> $userAgent
		]);
	}
	
	protected function returnImage(){
		$response = new Response();
		$response->header('Content-Type', 'image/gif')
		->setContent(base64_decode(self::GIF_ONE_PIXEL));
		return $response;
	}
	
	protected function returnFailed(){
		$response = new Response();
		$response->setStatusCode(Response::HTTP_BAD_REQUEST);
		return $response;
	}
	
	/**
	 * 分析user-agent
	 * @param unknown $userAgent
	 * @return unknown[]|string[]
	 */
	protected function parseUserAgent($userAgent){
		//set version style x.y.z
		DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_PATCH);
		
		$dd = new DeviceDetector($userAgent);
		$dd->setCache(new DeviceDetectorRedisCache());
		$dd->discardBotInformation();
		$dd->skipBotDetection();
		
		$dd->parse();
		
		if(!$dd->isBot()){
			$client = $dd->getClient();
			$os = $dd->getOs();
			$data = [
				'os'				=> isset($os['name'])?$os['name']:'other',
				'os_version'		=> isset($os['version'])?$os['version']:'0.0.0',
				'device'			=> $dd->getModel()?:$dd->getDeviceName(),
				'client_type'		=> $client['type'],
				'client_name'		=> $client['name'],
				'client_version'	=> $client['version'],
			];
			return $data;
		}
		return [];
	}
	
}