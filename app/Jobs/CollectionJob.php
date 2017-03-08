<?php
namespace App\Jobs;

use Symfony\Component\HttpFoundation\HeaderBag;
use App\Extensions\ElasticClient;
use App\Extensions\ProfileConfig;
use App\Extensions\Util;

class CollectionJob extends Job{
    
    private $_data;
    private $_log = null;
    
    public $support_type = ['event','pageview'];
    
    const COOKIE_KEY = '_jiaozi_uid';
    
    public function __construct(array $data){
        $this->_data = $data;
    }
    
    protected function log($message,array $data){
        if($this->_log == null){
            $this->_log = app('log');
        }
        $this->_log->info($message,$data);
    }
    
    public function handle(){
        $headers = new HeaderBag();
        $headers->add(array_get($this->_data, 'header',[]));
        
        if (! ($result = $this->getCommonData($this->_data,$headers))) {
            $this->log('request data parse failed', $this->_data);
            return;
        }
        if($result['type'] == 'pageview'){
            $result = array_merge($result,$this->getPageviewInfo($this->_data, $headers));
        }
        if($result['type'] == 'event'){
            $result = array_merge($result,$this->getEventInfo($this->_data));
        }
        
        ElasticClient::getInstance()->save($result);
    }
    
    protected function getPageviewInfo(array $request,HeaderBag $headers){
        $result = [];
        $result['referer'] = array_get($request,'query.referer','');
        
        $result['url'] = array_get($request,'query.url', null);
        
        if (is_null($result['url'])) {
            $result['url'] = $headers->get('referer', '');
        }
        
        if(array_has($request,'query.utm_source')){
            $result['utm_source'] = array_get($request,'query.utm_source');
            $result['utm_medium'] = array_get($request,'query.utm_medium', null);
            $result['utm_term'] = array_get($request,'query.utm_term', null);
            $result['utm_content'] = array_get($request,'query.utm_content', null);
            $result['utm_campaign'] = array_get($request,'query.utm_campaign', null);
        
            // referer为空，代表是direct直接来源
        } else if (empty($result['referer'])) {
            $result['utm_source'] = 'direct';
        
            // 判断referer和url是否是同一个host，不是则代表从其他地方跳转过来的
        } else {
            $r = parse_url($result['referer']);
            $l = parse_url($result['url']);
            if (isset($l['host']) && $r['host'] !== $l['host']) {
                $result['utm_source'] = $result['referer'];
            }
        }
        return $result;
    }
    
    protected function getEventInfo(array $request){
        $result = [
            'category' => array_get($request,'query.category', ''),
            'action' => array_get($request,'query.action', ''),
            'label' => array_get($request,'query.label', ''),
            'value' => array_get($request,'query.value', ''),
            'value_number' => array_get($request,'query.value_number', null)
        ];
        return $result;
    }
    /**
     * 每个数据采集，都会有公共信息的
     * 信息如下return：
     *
     * uuid 浏览器里面的cookie区分唯一浏览器或者手机IMEI
     * ip IP
     * timestamp 发送统计信息的设备IP
     * user_agent 发送统计信息的设备信息
     * os 发送统计信息的操作系统
     * os_version 操作系统版本
     * device Desktop ipad
     * client_type browser mobile app
     * client_name Chrome Facebook Stylewe-IOS
     * client_version 客户端版本
     *
     * @param array $request
     */
    protected function getCommonData(array $request,HeaderBag $headers)
    {
        //User UUID
        $uuid = array_get($request, 'cookie.'.self::COOKIE_KEY,null);
        if (is_null($uuid)) {
            $uuid = array_get($request,'query.'.self::COOKIE_KEY,null);
        }
        if (is_null($uuid)) {
            return false;
        }
        
        $type = array_get($request,'query.type',null); 
        if(!in_array($type, $this->support_type)){
            return false;
        }
        
        //get Profile ID
        $pid = array_get($request,'query.pid',null);
        if(is_null($pid)){
            return false;
        }
        
        $profile_name = ProfileConfig::getConfig($pid,'name');
        
        $userAgent = $headers->get('User-Agent');
        $ua = Util::parseUserAgent($userAgent);
        return array_merge($ua, [
            'uuid'          => $uuid,
            'ip'            => $request['ip'],
            'timestamp'     => $request['timestamp'],
            'user_agent'    => $userAgent,
            'user_id'       => array_get($request,'query.user_id',null),//site user id
            'type'          => $type,
            'profile_id'    => $pid,
            'profile_name'  => $profile_name,
            'country_code'  => $headers->get('CF_IPCOUNTRY'),
        ]);
    }
}