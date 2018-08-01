<?php
namespace App\Extensions;

use DeviceDetector\Parser\Device\DeviceParserAbstract;
use DeviceDetector\DeviceDetector;

class Util{
    
    private static $_parser = null;
    /**
     * 分析user-agent
     *
     * @param string $userAgent
     * @return string[]
     */
    public static function parseUserAgent($userAgent)
    {
        
        // user-agent app/1.2.3 (iPhone 7 Plus;iOS 11.2.6) Alamofire/4.2.0
        if(preg_match('/^(.*)\/(.*) \((.*);(.*) (.*)\) (.*)\/(.*)$/', $userAgent,$matches)){
            return [
                'os' => $matches[4],
                'os_version' => $matches[5],
                'device' => $matches[3],
                'client_type' => 'mobile app',
                'client_name' => $matches[1],
                'client_version' => $matches[2]
            ];
        }
        
        // user-agent app/1.2.3 iOS/11.2.6 (iPhone 7 Plus)
        if(preg_match('/^(.*)\/(.*) (.*)\/(.*) \((.*)\)$/', $userAgent,$matches)){
            return [
                'os' => $matches[3],
                'os_version' => $matches[4],
                'device' => $matches[5],
                'client_type' => 'mobile app',
                'client_name' => $matches[1],
                'client_version' => $matches[2],
            ];
        }
        
        if(static::$_parser==null){
            // set version style x.y.z
            DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_PATCH);
    
            $dd = new DeviceDetector();
            $dd->setCache(new DeviceDetectorRedisCache());
            $dd->discardBotInformation();
    
            static::$_parser = $dd;
        }else{
            $dd = static::$_parser;
        }
    
        $dd->setUserAgent($userAgent);
        $dd->parse();
    
        if (! $dd->isBot()) {
            $client = $dd->getClient();
            $os = $dd->getOs();
            return [
                'os' => isset($os['name']) ? $os['name'] : 'other',
                'os_version' => isset($os['version']) ? $os['version'] : '0.0.0',
                'device' => $dd->getModel() ?: $dd->getDeviceName(),
                'client_type' => $client['type'],
                'client_name' => $client['name'],
                'client_version' => $client['version']
            ];
            
        }
        return [];
    }
}