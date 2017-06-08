<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use Hash;
use JWTAuth;
use Illuminate\Support\Facades\Log;
class APIController extends Controller
{
    public function register(Request $request)
    {        
    	$input = $request->all();
    	$input['password'] = Hash::make($input['password']);
    	User::create($input);
        return response()->json(['result'=>true]);
    }
    
    public function login(Request $request)
    {
	    $input = $request->only('email', 'password');
    	if (!$token = JWTAuth::attempt($input)) {
            return response()->json(['success' => false, 'message' => 'Wrong email or password.']);
        }
        	return response()->json(['success' => true, 'token' => $token]);
    }
    
    public function get_user_details(Request $request)
    {
    	$input = $request->all();
    	$user = JWTAuth::toUser($input['token']);
        return response()->json(['result' => $user]);
    }
    
    public function ping(Request $req){
	
	    Log::info('all request headers from angular 2: ' . json_encode($req->header()));
	    
	    $res = json_decode('[{"static_ips":[{"ip_group":"NSW","available":5},{"ip_group":"QLD","available":5}],"gardens":["credit_prepaid","credit"],"shapes":["level_one","level_two"],"name":"2SG DSL Test Account","supplier_billing_reference_required":false,"id":1,"password_required":true,"network_type":"ppp_dsl"},{"static_ips":[],"gardens":[],"shapes":[],"name":"2SG NBN DHCP Test Account","supplier_billing_reference_required":true,"id":2,"password_required":true,"network_type":"ppp_nbn"}]');

	    return response()->json($res);
	    
    }
    
}
