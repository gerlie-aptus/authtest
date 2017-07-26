<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Request as RequestSegment;
use JWTAuth;
use Exception;
use Illuminate\Support\Facades\Log;
use DB;
use App\GroupModel;
use Illuminate\Support\Facades\Input;
class authorizedJWT
{
    public function handle(Request $request, Closure $next)
    {

	$token = str_replace("Bearer ","",$request->header("authorization"));
        $user  = JWTAuth::toUser($token);
        //$token = JWTAuth::getToken();
        //return response()->json(["message"=>$input]);
        $get_username = json_decode($user,true);
	$get_all = $request->all();

	Log::info("GET_REQUEST",[$get_all,$request->route("service_id")]);
        
	if($token) {
		
//            $fabio_service_id = $all['fabio_service_id'];
//            $fabio_service_id	  = 31825;
            $username        	  = $get_username['username'];
	    $segments	     	  = RequestSegment::segments();


	   //Temporary
		
		if(empty($get_all)){
		
			$fabio_service_id     = $segments[1];

	                Log::info("All Request",[$get_all]);
		
	        }else {

                 Log::info("All Request here",[$get_all['fabio_service_id'],$get_all,$request->headers->all()]);
		 $fabio_service_id = $get_all['fabio_service_id'];
        	}


	   //END

//	    $fabio_service_id     = $segments[1];
//	    $fabio_service_id     = 31826;
//	    Log::info("offset",$segments);
	    $all = $request->all();
	    $get_fabio_service_id = $all;
	    	
	   Log::info("services",[$segments,$all]); 
	   Log::info("offset",[$segments]);
		
	if($segments[0] == "radius_service_id"){

		$fabio_service_id = $fabio_service_id;
		Log::info("radius_request_get",[$fabio_service_id]);
	}else{
		
		if($segments[0] == "service"){

		 $fabio_service_id = $request->session()->get('key');
                  Log::info("services_request_get",[$request->session()->get('key')]);
	

	        }else{

			$fabio_service_id = $get_fabio_service_id['fabio_service_id'];
                        Log::info("services_management",[$get_fabio_service_id]);

		}
	}
/*
		
*/

/*		
 
*/	

/*	    Log::info("middleware_service",[$segments[0]]);            
	    Log::info("middleware",[$get_fabio_service_id,$segments]);            
*/
            $get_acct = GroupModel::groups($username, $fabio_service_id);
 	    $type = gettype($get_acct);
		Log::info("Groupmodel",[$get_acct,$type]);
	
		$get_account_num_session = $request->session()->put('key', $get_acct[0]->user_service_id);
		Log::info("Session",[$request->session()->get('key')]);
                
		if($get_acct[0]->Tag < 1){
                  
		  return response()->json(["message"=>"Unauthorized"]);
               	   
		 }
                
                return $next($request);   
            } else {
                return response()->json([
                        'success' => false,
                        'message' => 'Wrong email or password.',
                        ]);
            }
	         

    }
}

