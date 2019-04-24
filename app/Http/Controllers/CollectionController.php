<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\CollectionJob;
use Illuminate\Support\Facades\Log;

class CollectionController extends Controller
{

    const GIF_ONE_PIXEL = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw';
    const MAX_TIME_DIFF = 43200;//60*60*12
    
    public function collect(Request $request){
        $clientTime = intval($request->query('timestamp',time()));
        $now = time();
        if(abs($clientTime-$now) > self::MAX_TIME_DIFF){
            $clientTime = $now;
            Log::info("much client_time diff url:".$request->url());
        }
        $request->setTrustedProxies(['0.0.0.0/0'],Request::HEADER_X_FORWARDED_ALL);
        $this->dispatch(new CollectionJob([
            'cookie'    => $request->cookies->all(),
            'query'     => $request->query->all(),
            'header'    => $request->headers->all(),
            'timestamp' => $clientTime,
            'ip'        => $request->getClientIp(),
        ]));
        return response(base64_decode(self::GIF_ONE_PIXEL),Response::HTTP_OK,['Content-Type'=>'image/gif']);
    }
    
    public function blank(){
        return 'hello world';
    }
}