<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\CollectionJob;

class CollectionController extends Controller
{

    const GIF_ONE_PIXEL = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw';
    
    public function collect(Request $request){
        
        $this->dispatch(new CollectionJob([
            'cookie'    => $request->cookies->all(),
            'query'     => $request->query->all(),
            'header'    => $request->headers->all(),
            'timestamp' => time(),
            'ip'        => $request->getClientIp(),
        ]));
        return response(base64_decode(self::GIF_ONE_PIXEL),Response::HTTP_OK,['Content-Type'=>'image/gif']);
    }
    
    public function blank(){
        return 'hello world';
    }
}