<?php
namespace App\Extensions;

class ProfileConfig{
    
    /**
     * Client ID generated using Hashids
     * salt => chicv702
     * padding => 16
     * seed => abcdefghijklmnopqrstuvwxyz0123456789
     * @var unknown
     */
    private static $_configs = [
        
        '2jynd17ykm6v9wlq'  => [
            'name' => 'StyleWe_Web'
        ],
        
        '1903r64epmy28kjq'  => [
            'name' => 'StyleWe_Android_App'
        ],
        
        '2lyk03mr57x1rpv5'  => [
            'name' => 'StyleWe_IOS_App'
        ],
        
        'xw2ne84xo4ro160p'  => [
            'name' => 'JustFashionNow_Web'
        ],
        
        'zpw31v76e40yxoe6'  => [
            'name' => 'JustFashionNow_Android_App'
        ],
        
        'v3w1yr78q7x0j5ze'  => [
            'name' => 'JustFashionNow_IOS_App'
        ],
        
        'wz9q1n71d7823e6j' => [
            'name' => 'PopJulia_Web'
        ],
        
        'e9n6d142972p3lzj' => [
            'name' => 'PopJulia_Android_App'
        ],
        
        'r1vpx07kz7k25ne3' => [
            'name' => 'PopJulia_IOS_App'
        ]
        
    ];
    
    public static function getConfig($profile_id,$key=null){
        $r = array_get(static::$_configs,$profile_id);
        if($key){
            $r = array_get($r,$key);
        }
        return $r;
    }
}