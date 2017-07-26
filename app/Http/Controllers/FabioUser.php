<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User; 
use Hash;
use JWTAuth;
use DB;

class FabioUser extends Controller
{

    public function fabio_login(Request $request) {
	if($request->getContent()){
            parse_str($request->getContent(), $params);

            $username = $params['username'];
            $password = $params['password'];
        }
        
        $input = array(
                    "username" => $username,
                    "password" => $password
                    );

            /*input form will be passed here*/
//            $token = JWTAuth::attempt($input);

        //$input = $request->only("username","password");
        if (!$token = JWTAuth::attempt($input)) {
            return response()->json(['result' => 'wrong email or password!!!!.',$input]);
        }
            return response()->json(['result' => $token]);


    }    
       

}



    
