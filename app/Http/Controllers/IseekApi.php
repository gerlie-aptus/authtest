<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Request as SegmentRequest;
use \GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use App\UserservicesModel;
use Illuminate\Support\Facades\Log;
class IseekApi extends Controller
{

/*
* declare var
*/
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

    // get accounts
    public function accounts(){
        return $this->api_call('accounts');
    }

    // post services
    public function add_service(Request $request) {
        $this->data_input = array(
            "account"                   => $this->account_num,
            "username"                  => $request->input("username"),
            "password"                  => $request->input("password"),
            "supplier_billing_reference" => $request->input("supplier_billing_reference")
        );

        $this->api_call("services", "post");    
    }

    // get service
    public function service($service_id) {
        return $this->api_call("service/".$service_id);
    }

    // put service
    public function update_service($service_id, Request $request) {
        $this->data_input = array(
    'password'=>$request->input("password") //encrypt this
    );

        $this->api_call("service/".$service_id, "put");
    }

    // put service
    public function manage_service($service_id, Request $request) {
        // $service_id = 29;
	$fabio_serviceid = $request->all();
	Log::info("segments",[$fabio_serviceid['fabio_service_id'],$service_id]);

        $password_change = array();
        $static_ip_change = array();
        $garden_add = array();
        $shape_add = array();
        $do_kick_user = array();
        $res = array();

        $fabio_service_id = $request->input('fabio_service_id');
        $fabio_update_details = array();

    // update password if not empty
        $passwords = $request->input("passwords");
        $password = $passwords['password'];
        $confirm_password = $passwords['confirmPassword'];

        if($password !== $confirm_password){
            $this->errors['errors']['passwords'] = ["description" => "Password and password confirmation doesn't match."];
        } else {
            if ($password && $password != null) {
                $this->data_input = array(
                'password' => $password //encrypt this
                );

                $password_change = $this->api_call("service/".$service_id, "put");

                if(isset($password_change['error'])){
                    $this->errors['errors']['passwords'] = $password_change['error'];
                } else {
                    $fabio_update_details['radius_password'] = $password;
                    $res['password'] = $password_change;
                }
            }
        }

    // update static IP if not empty
        $ip_group = $request->input("ip_group");

        if ($ip_group &&  $ip_group != null) {
            $ip_group_change = $this->api_call("service/".$service_id."/static_ip/".$ip_group, "put");

            if(isset($ip_group_change['error'])){
                $this->errors['errors']['ip_group'] = $ip_group_change['error'];
            } else {
                $fabio_update_details['radius_static_ip'] = $ip_group_change['body']->assigned_ip;
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
                $fabio_update_details['radius_garden'] = $garden;
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
                $fabio_update_details['radius_shape'] = $shape;
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

        //update database of Fabio, user_services table to save radius details
        Log::info('Radius Fabio Details:');
        Log::info($fabio_update_details);
        Log::info($fabio_service_id);
        $updated_fabio = $this->update_fabio_services($fabio_update_details, $fabio_service_id);
        if($updated_fabio){
            $res['info']['success'] = "Successfully updated Radius details in Fabio.";
        } else {
            $res['info']['error'] = "Failed on updating Radius details in Fabio.";
        }

        return $res; 
    }
        

// add servce to radius
    public function create_service(Request $request) {
        /*if($request->getContent()){
            parse_str($request->getContent(), $params);
            $username = $params['username'];
            $password = $params['password'];
            $sbr = $params['sbr'];
        } else {*/
            $username = $request->input("username");
            $passwords = $request->input("passwords");
            $password = $passwords['password'];
            $sbr = (string)$request->input("sbr");
        //}

        if ($username && $password && $username != null && $password != null) {
            // api call to iseek to create service
            $this->data_input = array(  'account' => $this->account_num,
                'username' => $username,
                'password' => $password,
                'supplier_billing_reference' => $sbr);
            $add_service = $this->api_call("services", "post");

            if(isset($add_service['error'])){
                $this->errors['errors']['add_service'] = $add_service['error'];
            } else {
                $res['add_service'] = $add_service;

                $radius_service_id = $add_service['body']->id;
                $fabio_service_id = $request->input("user_services_id");

                // save radius details to fabio user_services
                $update_fields = array(
                    'radius_service_id' => $radius_service_id,
                    'radius_username' => $username, 
                    'radius_password' => $password,
                    );
                $this->update_fabio_services($update_fields, $fabio_service_id);

                $res['add_service'] = $add_service;
            }

            if(!empty($this->errors)){
                $res['messages'] = $this->errors;
            }

            return $res; 
        } else {
            return json_encode(["messages" => ["errors" => ["add_service" => ["description" => "Incomplete service details to save."]]]]);
        }
    }

    public function test() {
        $update_fields = array(
            'radius_service_id' => 1,
            'radius_username' => 'hello@test.com',
            'radius_password' => 'testttt',
            );
        $res   = $this->update_fabio_services($update_fields, $fabio_service_id);

        var_dump($res);
    }

    public function update_fabio_services($fields=array(), $fabio_service_id) {
        /*if all data inputs has no value, no records will be updated*/
        if(!array_filter($fields)) {
            $message = json_encode(['message'=>'Nothing to update']); 

            return $message;
        } else {
            /*filter data only data inputs with value*/			
            $array_data = array_filter($fields);
            $update_data = UserservicesModel::update_radius($fields, $fabio_service_id);

            return $update_data; // if records is successfully updated it will return 1 if not 0.
        }
    }

// delete static_ip
    public function service_delete_staticip($service_id) {
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
    public function service_delete_garden($service_id) {
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
    public function service_delete_shape($service_id) {
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

    public function register(Request $request) {
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        User::create($input);
        return response()->json(['result'=>true]);
    }

    public function login(Request $request) {
        $input = $request->all();

        if (!$token = JWTAuth::attempt($input)) {

            return response()->json(['result' => 'wrong email or password.']);
        }

        return response()->json(['token' => $token]);
    }

    public function get_user_details(Request $request) {
        $input = $request->all();
        $user = JWTAuth::toUser($input['token']);
        return response()->json(['result' => $user]);
    }

    public function login_user() {
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

            $resBody = $response->getBody()->getContents();
            $body =  $resBody == null || empty($resBody) ? array('body' => array('res' => 'No content')) : $resBody;

            if(gettype($body) == "string"){
                $body = json_decode($body);
            }

            $result = array(
                "header"       => $response->getHeader("Content-Type"),
                "statusCode"   => $response->getStatusCode(),
                "reason"       => $response->getReasonPhrase(),
                "body"         => $body
                );

            return $result;
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

    public function get_user_services_id($service_id) {
     //$service_id = "31825";    
    $get_service_id = UserservicesModel::get_services_id($service_id);
	
//	Log::info("USERSERVICES",[SegmentRequest::segments()]);	
        if($get_service_id)
        {
            return $get_service_id;  
        } else {
            return json_encode(["message" => "The service doesn't exist in Fabio system."]);
        }
    }
}
