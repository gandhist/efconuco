<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\StockInventory;
use App\PurchaseRequestModel;

class InventoryController extends Controller
{
    //
    public function index()
    {
        return view('inventory.index');
    } // end of index

    public function inventory_list(Request $request)
    {
        $columns = array(
            0 => 'id'
        );

        $totalData = PurchaseRequestModel::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada get pada pencarian beban_id
        if (empty($request->input('search.value'))) {
            $lists = StockInventory::select(DB::raw('count(stock_inventory.is_taken) as current_stock, stock_inventory.*'))
            ->where('stock_inventory.is_taken','0')->groupBy('stock_inventory.id_barang')
                                ->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        }
        else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $lists = StockInventory::whereHas('master_baja', function($query){
                $query->where("nama","LIKE","%{$search}%");
            })->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = StockInventory::where('beban_id','LIKE', "%{$search}%")
                                    ->orWhere('trans_no','LIKE',"%{$search}%")
                                    ->count();
        }
        //collection data here
        $data = array();
        $no = 1;
        if (!empty($lists)) {
            foreach ($lists as $ro) {
                $details = url('inventory/show', $ro->id_barang);
                $row['no'] = $no;
                $row['nama_barang'] = $ro->master_baja->nama;
                $row['kode_barang'] = $ro->master_baja->kode_barang;
                $row['pr_number'] = $ro->header->pr_number;
                $row['current_stock'] = $ro->current_stock;
                $row['paid_date_file'] = "<a target='_blank' href=".url('uploads/file_service_kendaraan/' .$ro->paid_date_file).">"."Bukti Bayar</a>";
                if ($ro->header->status == "APPROVED") {
                    $row['status'] = "<span class='label label-success'> APPROVED</span>";
                } else if ($ro->header->status == "REJECTED") {
                    $row['status'] = "<span class='label label-danger'> REJECTED</span>";
                } else if ($ro->header->status == "CANCEL") {
                    $row['status'] = "<span class='label label-warning'> CANCEL</span>";
                } else if ($ro->header->status == "PAID"){
                    $row['status'] = "<span class='label label-primary'> PAID</span>";
                };
                $row['remarks'] = $ro->remarks;
                $row['action'] = "<a href=' $details ' class='btn btn-success btn-xs'><span class='fa fa-eye'></span></a>";
                $data[] = $row;
                $no++;
            }
        }
        // return data json
        $jsonData = array(
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        );

        echo json_encode($jsonData);
    }

    public function show($id)
    {
        $data = StockInventory::where('id_barang',$id)->get();
        //return $data;
        return view('inventory.show', compact('data'));
    } // end of method show


} // end of inventory controller
