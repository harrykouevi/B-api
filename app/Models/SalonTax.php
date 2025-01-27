<?php
/*
 * File name: SalonTax.php
 * Last modified: 2024.04.11 at 14:51:04
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonTax extends Model
{
    use HasFactory;

    public $table = 'salon_taxes';
    public $timestamps = false;
}
