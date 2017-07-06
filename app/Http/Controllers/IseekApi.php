<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use App\UserservicesModel;

use Illuminate\Support\Facades\Log;

class IseekApi extends Controller
{

    ////declare var
    private $data_input = array();
    private $errors = array();
    private $account_num = 1;

    /*
    * Setters
    */
    public function set_var($var, $value){
        $this->$var = $value;
    }

    /*
    * Getters
    */
    public function get_var($var){
        return $this->$var;
    }


    public function index() {   
        return $this->api_call("ping");
    }

    //get accounts
    public function accounts(){
        return $this->api_call('accounts');
    }

    // post services
    public function add_service(Request $request)
    {
        $this->data_input = array(
           "account"                   => $request->input("account"),
           "username"                  => $request->input("username"),
             "password"                  => $request->input("password"), //gerlie: encrypt this
             "supplier_billing_reference" => $request->input("supplier_billing_reference")
             );

        $this->api_call("services", "post");    
    }

    // get service
    public function service($service_id) {
        return $this->api_call("service/".$service_id);
    }

    // put service
    public function update_service($service_id, Request $request)
    {
        $this->data_input = array(
             'password'=>$request->input("password") //encrypt this
             );

        $this->api_call("service/".$service_id, "put");
    }

    // put service
    public function manage_service($service_id, Request $request)
    {
        //$service_id = 26; //$request->input("service_id"); //test3@isg.com.au
        // $service_id = 27; //$request->input("service_id"); test3@2sg.com.au
        //$service_id = $request->input("service_id"); // test3@2sg.com.au

        $password_change = array();
        $static_ip_change = array();
        $garden_add = array();
        $shape_add = array();
        $do_kick_user = array();
        $res = array();

        // update password if not empty
        $passwords = $request->input("passwords");
        $password = $passwords['password'];

        if ($password &&  $password != null) {
            $this->data_input = array(
                'password' => $password //encrypt this
                );

            $password_change = $this->api_call("service/".$service_id, "put");

            if(isset($password_change['error'])){
                $this->errors['errors']['passwords'] = $password_change['error'];
            } else {
                $res['password'] = $password_change;
            }
        }

        // update static IP if not empty
        $ip_group = $request->input("ip_group");

        if ($ip_group &&  $ip_group != null) {
            $ip_group_change = $this->api_call("service/".$service_id."/static_ip/".$ip_group, "put");
            
            if(isset($ip_group_change['error'])){
                $this->errors['errors']['ip_group'] = $ip_group_change['error'];
            } else {
                $res['ip_group'] = $ip_group_change;
            }
        }

        // add wall garden if not null
        $garden = $request->input("garden");

        if ($garden &&  $garden != null) {
            $garden_add = $this->api_call("service/".$service_id."/garden/".$garden, "put");

            if(isset($garden_add['error'])){
                $this->errors['errors']['garden'] = $garden_add['error'];
            } else {
                $res['garden'] = $garden_add;
            }
        }

        //add shape if not null
        $shape = $request->input("shape");

        if ($shape &&  $shape != null) {
            $shape_add = $this->api_call("service/".$service_id."/shape/".$shape, "put");

            if(isset($shape_add['error'])){
                $this->errors['errors']['shape'] = $shape_add['error'];
            } else {
                $res['shape'] = $shape_add;
            }
        }

        // kick user if checked
        $kick_user = $request->input("kick_user");

        if ($kick_user &&  $kick_user != null) {
            $do_kick_user = $this->api_call("service/".$service_id."/kick", "post");
            $res['kick_user'] = $do_kick_user;

            if(isset($do_kick_user['error'])){
                $this->errors['errors']['kick_user'] = $do_kick_user['error'];
            } else {
                $res['kick_user'] = $do_kick_user;
            }

        }

        if(!empty($this->errors)){
            $res['messages'] = $this->errors;
        }
        
        return $res; 
    }
 

    // add servce to radius
    public function create_service(Request $request){
        $username = $request->input("username");
        $passwords = $request->input("passwords");
        $password = $passwords['password'];
        $sbr = (string)$request->input("sbr");

        if ($username && $password && $username != null && $password != null) {
            $this->data_input = array(  'account' => $this->account_num,
                'username' => $username,
                'password' => $password,
                'supplier_billing_reference' => $sbr);

            $add_service = $this->api_call("services", "post");

            Log::info('Showing add_service return: ');
            Log::info($add_service);
		
            if(isset($add_service['error'])){
                $this->errors['errors']['add_service'] = $add_service['error'];
            } else {
                 $res['add_service'] = $add_service;
                 Log::info('Showing radius id: ');
                 Log::info($add_service);
                 //Log::info($add_service['body']->id);

                $radius_service_id = $add_service['body']->id;
		        $radius_services_id = $request->input("user_services_id");

	            $update =  UserservicesModel::update_radius_services_id($radius_services_id, $radius_service_id); 
              
	            //return $update;
                $res['add_service'] = $add_service;
            }

            if(!empty($this->errors)){
                $res['messages'] = $this->errors;
            }
            
            return $res; 
        } else {
            return json_encode(["message" => "Incomplete service details to save."]);
        }
    }

    public function update_fabio_services(Request $request){
    
	$fields = [
                    'radius_service_id' => $request->input("radius_service_id"),
                    'radius_static_ip'  => $request->input("radius_static_ip"),
                    'radius_username'   => $request->input("radius_username"),
                    'radius_password'   => $request->input("radius_password"),
                    'radius_garden'     => $request->input("radius_garden"),
                    'radius_shape'      => $request->input("radius_shape"),
                  ];    
			
		/*if all data inputs has no value, no records will be updated*/

		if(!array_filter($fields))
		{
	     	   $message = json_encode(['message'=>'Nothing to update']); 
			
		   return $message;

		}else{
			
			/*filter data only data inputs with value*/			
			$array_data = array_filter($fields);
			$update_data = UserservicesModel::update_radius($fields);
			

			return $update_data; // if records is successfully updated it will return 1 if not 0.
		
		}
		


	
    }

    // delete static_ip
    public function service_delete_staticip($service_id)
    {
        $delete_staticip = $this->api_call("service/".$service_id."/static_ip", "delete");
        if(isset($delete_staticip['error'])){
            $this->errors['errors']['delete_staticip'] = $delete_staticip['error'];
        } else {
            $res['delete_staticip'] = $delete_staticip;
        }

        if(!empty($this->errors)){
            $res['messages'] = $this->errors;
        }
        
        return $res; 
    }

    // delete service garden
    public function service_delete_garden($service_id)
    {
        $delete_garden = $this->api_call("service/".$service_id."/garden", "delete");

        if(isset($delete_garden['error'])){
            $this->errors['errors']['delete_garden'] = $delete_garden['error'];
        } else {
            $res['delete_garden'] = $delete_garden;
        }

        if(!empty($this->errors)){
            $res['messages'] = $this->errors;
        }
        
        return $res; 
    }

    // delete service shaping
    public function service_delete_shape($service_id)
    {
        $delete_shape = $this->api_call("service/".$service_id."/shape", "delete");

        if(isset($delete_shape['error'])){
            $this->errors['errors']['delete_shape'] = $delete_shape['error'];
        } else {
            $res['delete_shape'] = $delete_shape;
        }

        if(!empty($this->errors)){
            $res['messages'] = $this->errors;
        }
        
        return $res; 
    }

    


/*
    // delete service
    public function delete_service($service_id) {
        $this->api_call("service/".$service_id, "delete");
    }

    // get service usage
    public function usage($service_id, $start, $end) {
        return $this->api_call("service/".$service_id."/usage?start=".$start."&end=".$end);
    }

    // get service auth log
    public function authlog($service_id,$start,$end)
    {
        $this->api_call("service/".$service_id."/authlog?start=".$start."&end=".$end);
    }

    // post service kick
    public function kick_service($service_id)
    {
        $this->api_call("service/".$service_id."/kick");
    }    

    

    // put service garden
    public function service_update_garden($service_id, $garden)
    {   
        $garden =  $request->input("garden");
        $this->data_input = array(
                'garden' => $garden
            );

        $this->api_call("service/".$service_id."/garden/".$garden, "put");
    }

    // get service history
    public function service_history($service_id)
    {
        $this->api_call("service/".$service_id."/history");
    }

    // put service shape
    public function service_update_shape($service_id, $shape)
    {
        $this->api_call("service/".$service_id."/shape/".$shape, "put");
    }

    // put service static ip
    //BRB ON THIS
    public function service_update_staticip($service_id, $ip_group)
    {
        $this->api_call("service/".$service_id."/static_ip/".$ip_group, "put");
    }

    // update user password
    public function update_user_password(Request $request)
    {
        $password = $request->input('password');
        $this->api_call("service/".$service_id."/static_ip/".$ip_group, "put");
    }
*/

    /*============================AUTHENTICATION============================*/

    public function register(Request $request)
    {
      $input = $request->all();
      $input['password'] = Hash::make($input['password']);
      User::create($input);
      return response()->json(['result'=>true]);
  }

  public function login(Request $request)
  {
    $input = $request->all();

    if (!$token = JWTAuth::attempt($input)) {

        return response()->json(['result' => 'wrong email or password.']);
    }

    return response()->json(['token' => $token]);
}

public function get_user_details(Request $request)
{
 $input = $request->all();
 $user = JWTAuth::toUser($input['token']);
 return response()->json(['result' => $user]);
}

public function login_user()
{
 $token = str_random(60);
 return json_encode(array("token"=>$token,'success'=>true)); 
}
/*===========================ENDAUTHENTICATION==========================*/





private function api_call($endpoint, $method = "GET"){
    try {
        $client = new Client(
            [
            'headers'  => ['Accept'   => 'application/json'],
            'verify'   => false,
            'base_uri' => env('BASE_URL')
            ]
            );

        $response = $client->request($method, $endpoint,
            [
            'auth' => [env("USER"),env("PASSWORD")],
            'json' => $this->data_input
            ]
            );

        $result = array(
            "header"       => $response->getHeader("Content-Type"),
            "statusCode"   => $response->getStatusCode(),
            "reason"       => $response->getReasonPhrase(),
            "body"         => json_decode($response->getBody()->getContents())
            );

        return $result == null || empty($result) ? array('body' => array('res' => 'No content')) : $result;
    } catch(ClientException $e) {
        $var = json_encode((string)$e->getResponse()->getBody());
        $body = json_decode($var,true);

        if(gettype($body) == "array"){
            return array('error' => $body);
        } else {
            return array('error' => json_decode($body));            
        }
    }

}

public function get_user_services_id($service_id)
{
       // $global_array = array();
    $get_service_id = UserservicesModel::get_services_id($service_id);

    if($get_service_id)
    {

       if($get_service_id['radius_service_id'] == null)
       {
        return $get_service_id;     
    }

    return $get_service_id;  
         //   $get_service_id = json_decode($get_service_id,true);

            //return $get_service_id[0];

	   //  foreach ($get_service_id as $key => $value) {

             //   if($value['radius_service_id'] == null)
               // {
                 //   return $value;     
               // }

             // }

            // return $get_service_id[0];

}
else
{
    return json_encode(["message" => "The service doesn't exist in Fabio system."]);
}
}



}
