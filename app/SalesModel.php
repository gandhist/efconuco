<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;

class SalesModel extends Model
{
    use softDeletes;
    protected $table = "so";
    //
}
