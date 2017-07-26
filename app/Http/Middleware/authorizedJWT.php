<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
/*use Request as RequestSegment;*/
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

	/*Get the header token*/
	$token = str_replace("Bearer ","",$request->header("authorization"));
	/*Get the authenticated user based on token*/
        $user  = JWTAuth::toUser($token);
	/*Decode the json response and get the username of authenticate user*/
        $get_username = json_decode($user,true);
	/*Get all the request input from services*/
	$get_all = $request->all();
	
	Log::info("Request_Service_ID",[$request->route('fabio_service_id')]);
        
	/*If the token is exist and correct*/
	if($token) {
	    /*Username of user who is currently logged in*/		
            $username = $get_username['username'];
		
		if(empty($get_all)){
		
			$fabio_service_id = $request->route('radius_service_id');
			if(!empty($fabio_service_id)){
	
				$fabio_service_id   = $request->route('radius_service_id');
				$request_service    = "fabio_id";	
			}else{
				if($fabio_service_id == null){
				
				$fabio_service_id = $request->route("service_id");
				$request_service  = "_service_id";
				Log::info("fabio service id",[$fabio_service_id]);				

				}
			}
	                Log::info("All Request",[$get_all,$fabio_service_id]);
		
	        }else {

                 Log::info("not empty get_all request",[$get_all['fabio_service_id']]);
		 $fabio_service_id = $get_all['fabio_service_id'];
		 $request_service = "fabio_id";

        	}



	   $get_fabio_service_id = $get_all;
	    	
	   Log::info("services_log",[$get_fabio_service_id,$request->route('service_id')]); 
		
           $get_acct = GroupModel::groups($username, $fabio_service_id,$request_service);
	
		Log::info("NULL",[$get_acct]);
                
		if(empty($get_acct) ){
                  
		  return response()->json(["message"=>"Unauthorized"]);
               	   
		 }else{

			if($get_acct[0]->Tag < 1){
				 return response()->json(["message"=>"Unauthorized"]);
			}
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

