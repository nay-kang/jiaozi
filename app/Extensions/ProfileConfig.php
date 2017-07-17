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
        
        //1
        '2jynd17ykm6v9wlq'  => [
            'name' => 'StyleWe_Web'
        ],
        
        //2
        '1903r64epmy28kjq'  => [
            'name' => 'StyleWe_Android_App'
        ],
        
        //3
        '2lyk03mr57x1rpv5'  => [
            'name' => 'StyleWe_IOS_App'
        ],
        
        //4
        'xw2ne84xo4ro160p'  => [
            'name' => 'JustFashionNow_Web'
        ],
        
        //5
        'zpw31v76e40yxoe6'  => [
            'name' => 'JustFashionNow_Android_App'
        ],
        
        //6
        'v3w1yr78q7x0j5ze'  => [
            'name' => 'JustFashionNow_IOS_App'
        ],
        
        //7
        'wz9q1n71d7823e6j' => [
            'name' => 'PopJulia_Web'
        ],
        
        //8
        'e9n6d142972p3lzj' => [
            'name' => 'PopJulia_Android_App'
        ],
        
        //9
        'r1vpx07kz7k25ne3' => [
            'name' => 'PopJulia_IOS_App'
        ],
        
        //10
        'q5912l495mnrey3d' => [
            'name' => 'ChicHola_Web'
        ],
        
        //11
        'v01xzk45pmodnpl2' => [
            'name' => 'ChicHola_Android_App'
        ],
        
        //12
        'oj56lpm3q7nwz8x3' => [
            'name' => 'ChicHola_IOS_App'
        ],
        
        //13
        'jgd6vz4lwmo2n581' => [
            'name' => 'FashionJodi_Web'
        ],
        
        //14
        'olk0jzmdym6n29w1' => [
            'name' => 'FashionJodi_Android_App'
        ],
        
        //15
        'px2wly4vj4de1j0q' => [
            'name' => 'FashionJodi_IOS_App'
        ],
        
        //16
        'njgrlemwp786zy9d' => [
            'name' => 'Noracora_Web'
        ],
        
        //17
        'e20qz8mgq4ky63dl' => [
            'name' => 'Noracora_Android_App'
        ],
        
        //18
        'vd9q3x7q6m81np06' => [
            'name' => 'Noracora_IOS_App'
        ],
    ];
    
    public static function getConfig($profile_id,$key=null){
        $r = array_get(static::$_configs,$profile_id);
        if($key){
            $r = array_get($r,$key);
        }
        return $r;
    }
}