<?php
namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BroadcastEventJob extends Job
{

    /**
     *
     * @var \GuzzleHttp\Client
     */
    private static $httpClient = null;

    private $_data;

    public function __construct(String $id, array $data)
    {
        $this->_data = $data;
        $this->_data['_id'] = $id;
    }

    public function handle()
    {
        if (! static::$httpClient) {
            static::$httpClient = new Client();
        }
        $broadcast_url = config('profile.' . $this->_data['profile_id'] . '.broadcast_url', null);
        ;
        if (! $broadcast_url) {
            Log::error("broadcast url not found", $this->_data);
            return;
        }
        $data = $this->_data;
        unset($data['profile_id']);
        unset($data['profile_name']);
        $data = array_dot($data);
        static::$httpClient->request("GET", $broadcast_url, [
            'query' => $data
        ]);
    }
}