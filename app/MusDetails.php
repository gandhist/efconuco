<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MusDetails extends Model
{
    //
    use softDeletes;
    protected $table = "mus_details";
    protected $guarded = ['id'];

}
