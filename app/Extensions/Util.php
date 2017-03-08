<?php
namespace App\Extensions;

use DeviceDetector\Parser\Device\DeviceParserAbstract;
use DeviceDetector\DeviceDetector;
use App\Extensions\DeviceDetectorRedisCache;

class Util{
    
    private static $_parser = null;
    /**
     * 分析user-agent
     *
     * @param unknown $userAgent
     * @return unknown[]|string[]
     */
    public static function parseUserAgent($userAgent)
    {
        /*
         * Custom User-Agent:
         * StyleWeShopping/1.2.3 Android/5.1.0 (GALAXY S5)
         * StyleWeShopping/1.2.3 iOS/9.3.4 (iPhone 6s)
         */
        if (preg_match('/StyleWeShopping\/([0-9\.]+) (Android|iOS)\/([0-9\.]+) \((.*)\)/', $userAgent, $matches)) {
            return [
                'os' => $matches[2],
                'os_version' => $matches[3],
                'device' => $matches[4],
                'client_type' => 'mobile app',
                'client_name' => 'StyleWeShopping',
                'client_version' => $matches[1]
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