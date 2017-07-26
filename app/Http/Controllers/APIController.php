<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use App\GroupModel;
use Hash;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use DB;

class APIController extends Controller
{
    public function register(Request $request) {        
    	$input = $request->all();
    	$input['password'] = Hash::make($input['password']);
    	User::create($input);
        return response()->json(['result'=>true]);
    }
    
    public function login(Request $request) {
        if($request->getContent()){
            parse_str($request->getContent(), $params);

            $username = $params['username'];
            $password = $params['password'];
            $fabio_service_id = $params['fabio_service_id'];
        } else {
            $username = $request->input("username");
            $password = $request->input("password");
            $fabio_service_id = $request->input("fabio_service_id");
        }

	   $input = array(
                    "username" => $username,
                    "password" => $password
                    );

	    /*input form will be passed here*/
	    $token = JWTAuth::attempt($input);

	    if($token){
            $get_acct = GroupModel::groups($username,$fabio_service_id);


                    if($get_acct[0]->Tag == 1)
                    {
                        return response()->json(['success' => true, 'token' => $token]);
                    }
                    else
                    {
                        echo json_encode(["message"=>"User not allowed to edit this service.","rawr"=>$get_acct]);
                    }
        } else {
            return response()->json([
                    'success' => false,
                    'message' => 'Wrong email or password.',
                    ]);
        }
	


 }

    public function get_user_details(Request $request) {
    	$input = $request->all();
    	$user = JWTAuth::toUser($input['token']);
        return response()->json(['result' => $user]);
    }
    
    public function ping(Request $req) {
	
	    Log::info('all request headers from angular 2: ' . json_encode($req->header()));
	    
	    $res = json_decode('[{"static_ips":[{"ip_group":"NSW","available":5},{"ip_group":"QLD","available":5}],"gardens":["credit_prepaid","credit"],"shapes":["level_one","level_two"],"name":"2SG DSL Test Account","supplier_billing_reference_required":false,"id":1,"password_required":true,"network_type":"ppp_dsl"},{"static_ips":[],"gardens":[],"shapes":[],"name":"2SG NBN DHCP Test Account","supplier_billing_reference_required":true,"id":2,"password_required":true,"network_type":"ppp_nbn"}]');

	    return response()->json($res);
	    
    }
   


    
}
