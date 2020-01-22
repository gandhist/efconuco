<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PurchaseRequestModel extends Model
{
    //
    use SoftDeletes;
    protected $table = "pr";
    protected $guarded = ['id'];
}
