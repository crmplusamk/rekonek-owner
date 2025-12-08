<?php

namespace App\Libraries;

use Illuminate\Database\Eloquent\Model;

class AppProperties extends Model
{
    public static function Properties()
    {
        return [
            'logo'=>'assets/images/logo_mini.png',
            'copyright'=>'<strong>&copy '.date('Y').' CRM+</strong> | All rights reserved',
            'version'=>'beta',
            'author'=>'PT Ayo Menebar Kebaikan',
            'description'=>env('APP_NAME').',Customer Management System',
        ];
    }
    public static function getProperties($sel)
    {
        return (isset(self::Properties()[$sel]))?self::Properties()[$sel]:null;
    }
}
