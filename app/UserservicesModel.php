<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\UserservicesModel;


class UserservicesModel extends Model
{

    protected $table = "user_services";
    
    public $timestamps = false;

    public static function get_services_id($service_id)
    {
	/*Get record based on the parameter*/
        $get_services_id = UserservicesModel::where("id",$service_id)->first();

        /*if record exist return record*/
        if($get_services_id)
        {
            return $get_services_id;
        }else{

            return false;
        }
    }

    public static function update_radius_services_id($radius_services_id,$radius_service_id)
    {
	
	$update_services_id = UserservicesModel::where("id",$radius_services_id)->update(['radius_service_id'=>$radius_service_id]);

	return $update_services_id;


    }

   public static function update_radius($fields, $fabio_service_id)
   {
	
	/*Filter the array data and get only data elements with values*/
	$fields =  array_filter($fields);
	
	/*itemporary static ip for now and update record base on the on the fields inputs*/
	$update_services = UserservicesModel::where("id",$fabio_service_id)->update($fields);
	return $update_services;
   }

   
}

