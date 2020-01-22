<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockInventory extends Model
{
    //
    use SoftDeletes;
    protected $table = "stock_inventory";
    protected $guarded = ['id'];

    public function master_baja()
    {
        return $this->belongsTo('App\MasterStock','id_barang');
    }

    public function header()
    {
        return $this->belongsTo('App\PurchaseRequestModel','pr_header');
    }
}
