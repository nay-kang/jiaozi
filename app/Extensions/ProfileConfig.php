<?php
namespace App\Extensions;

class ProfileConfig{
    
    /**
     * Client ID generated using Hashids
     * salt => jiaozi_reborn
     * padding => 16
     * seed => abcdefghijklmnopqrstuvwxyz0123456789
     */
    private static $_configs = [
        
        //1
        'jn321grq8rwvp5q6'  => [
            'name' => 'example_app_1'
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