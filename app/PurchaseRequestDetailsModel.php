<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PurchaseRequestDetailsModel extends Model
{
    //
    use SoftDeletes;
    protected $table = "pr_details";
    protected $guarded = ['id'];
}
