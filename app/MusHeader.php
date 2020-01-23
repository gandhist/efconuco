<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MusHeader extends Model
{
    //
    use softDeletes;
    protected $table = "mus";
}
