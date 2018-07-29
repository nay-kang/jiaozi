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