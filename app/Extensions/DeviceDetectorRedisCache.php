<?php
namespace App\Extensions;

use DeviceDetector\Cache\Cache as DDCache;
use Illuminate\Support\Facades\Cache;

class DeviceDetectorRedisCache implements DDCache
{

    const CACHE_KEY = 'UA_PARSE';

    private static $data = null;

    private function getData()
    {
        if (is_null(static::$data)) {
            static::$data = Cache::get(self::CACHE_KEY) ?: [];
        }
        return static::$data;
    }

    public function saveData(array $data)
    {
        return Cache::put(self::CACHE_KEY, $data, 180);
    }

    public function fetch($id)
    {
        $data = static::getData();
        return isset($data[$id]) ? $data[$id] : false;
    }

    public function contains($id)
    {
        $data = static::getData();
        return isset($data[$id]);
    }

    public function save($id, $value, $lifeTime = 0)
    {
        $data = static::getData();
        $data[$id] = $value;
        return $this->saveData($data);
    }

    public function delete($id)
    {
        $data = static::getData();
        unset($data[$id]);
        return $this->saveData($data);
    }

    public function flushAll()
    {
        return Cache::forget(self::CACHE_KEY);
    }
}