<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use JWTAuth;
use Exception;
use Illuminate\Support\Facades\Log;

class authJWT
{
    public function handle(Request $request, Closure $next)
    {
        try {
            //$user = JWTAuth::toUser($request->input('token'));
	        $user = JWTAuth::parseToken()->authenticate();
	        Log::info('user from JWTAuth parseToken:' . $user);
	        
	        $token = JWTAuth::getToken();
	        Log::info('token from JWTAuth:' . $token);
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['error'=>'Token is Invalid']);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['error'=>'Token is Expired']);
            }else{
                return response()->json(['error'=>'Something is wrong']);
            }
        }
        return $next($request);
    }
}
