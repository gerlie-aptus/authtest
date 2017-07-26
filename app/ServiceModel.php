<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\ServiceModel;

use DB;

class ServiceModel extends Model
{

    // protected $table = "groups";
    // protected $table = "module_permission";

    protected $table = "user";

    public static function groups($username,$fabio_service_id){

         $permission = DB::select("select count(*) as Tag, user_services.id as user_service_id, user_services.account_number,customer.name 
                            from user_services 
                            join customer ON customer.account_number = user_services.account_number 
                            join groups ON groups.groupname=user_services.account_number 
                                AND groups.groupmember='$username' where user_services.radius_service_id='$fabio_service_id'");

        return $permission;

    }
}

