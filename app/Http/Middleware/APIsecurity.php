<?php

namespace App\Http\Middleware;

use Tymon\JWTAuth\JWTAuth;
use Illuminate\Routing\ResponseFactory as ResponseFactory;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as Dispatcher;
use \Tymon\JWTAuth\Middleware\GetUserFromToken;
use \Illuminate\Http\Response as Res;
use Closure;

class APIsecurity extends GetUserFromToken
{
    protected $statusCode = Res::HTTP_OK;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
/*    public function handle($request, Closure $next)
    {
        return $next($request);
    }*/

    public function __construct(ResponseFactory $response, JWTAuth $auth, Dispatcher $events){
        parent::__construct($response, $auth, $events);
    }

    public function handle($request, Closure $next)
    {   
        $a = $request->all();
        print_r($a);
        $nonce = $request->header(env('NONCE'), '');
        $token = $this->auth->setRequest($request)->getToken();

        $clientSignature = $request->header(env('SIGNATURE'), '');
        $serverSignature = $this->calculateSignature($request, $nonce, $token);
        Log::info($serverSignature);
        if($serverSignature !== $clientSignature)
            return response()->json([
                      'status_code' => $this->getStatusCode('statusCodeTechError'),
                      'message_code' => $this->getMessageCode('msgCodeTechError'),
                      'message' => 'Invalid security headers'
              ], $this->getStatusCode('statusCodeTechError'));
        return $next($request);
    }

    public function calculateSignature($request, $nonce, $token)
    {
        $data = $request->all();
        Log::info($data);
        $attributes = http_build_query($data);
        Log::info($attributes);
        $pubKey = env('PUB_KEY', '');
        $encFunc = env('SIGNATURE_ENC_ALGO', '');
        Log::info($attributes . $token . $nonce . $pubKey);
        return $encFunc($attributes . $token . $nonce . $pubKey);
    }
}
