<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\StockInventory;
use App\MusHeader;
use App\MusDetails;
use App\MasterUom;
use App\MasterStock;
use App\MasterVendor;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return view('mus.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $data['uom'] = MasterUom::all();
        $data['master_baja'] = MasterStock::all();
        $data['vendor'] = MasterVendor::all();
        // running number
        $code = "/MUS/";
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');

        $rn_a = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
            $data['trans_no'] = sprintf('%04d', $rn[0]->rn+1).$code.$tahun.$bulan;
        }
        // end of running number
        return view('mus.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'=>'required',
            'nama_pembeli'=>'required'
        ]);

        // validasi item
        $px_nama = "item_name_";
        $px_desc = "item_desc_";
        $px_qty = "qty_item_";
        $dataitem_validate = array();
        for ($i=1; $i <= 20 ; $i++) { 
            if ($request->has($px_nama.$i)) {
                $dataitem_validate += 
                [
                    $px_desc.$i => 'required',
                    $px_qty.$i => 'required|regex:/^[0-9]+$/',
                ];
            } // end if
        } // end for
        $request->validate($dataitem_validate);
        // store header
        $header = New MusHeader();
        $header->tanggal = $request->tanggal;
        $header->trans_no = $request->trans_no;
        $header->request_by = $request->nama_pembeli;
        $header->remarks = $request->desc;
        $header->created_by = Auth::id();
        $header->created_at = Carbon::now()->toDateTimeString();
        $simpan_header = $header->save();
        // store details
        if ($simpan_header) {
            // looping details barang
            for ($i=1; $i <= 20 ; $i++) { 
                if ($request->has($px_nama.$i)) {
                    $data_details = [
                        'mus_header' => $header->id,
                        'trans_no' => $request->trans_no,
                        'id_barang' => $request->input($px_nama.$i),
                        'qty' => $request->input($px_qty.$i),
                        'desc' => $request->input($px_desc.$i),
                        'created_by' => Auth::id(),
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ];
                    $details = New MusDetails();
                    $save_details = $details->create($data_details);
                    // jika berhasil save details maka update is taken dan field di tables stock inventory
                    if ($save_details) {
                        for ($s=1; $s <= $request->input($px_qty.$i); $s++) { 
                            DB::table('stock_inventory')
                            ->where('id_barang', $request->input($px_nama.$i))
                            ->where('is_taken', 0)
                            ->orderBy('id','asc')
                            ->limit(1)
                            ->update(
                                [
                                    'is_taken' => 1,
                                    'taken_by' => $request->nama_pembeli,
                                    'no_sales_order' => $request->trans_no,
                                    'updated_by' => Auth::id(),
                                    'updated_at' => Carbon::now()->toDateTimeString()
                                ]
                            );
                        }
                        
                    } // enf if update stock inventory
                    
                } // end if
            } // end for loop
            if ($save_details) {
                return response()->json([
                    'status' => true,
                    'message' => 'Pengambilan Barang berhasil di simpan'
                ], 202);    
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'Pengambilan Barang gagal di simpan'
                ], 404);    
            }
        }
        
    }

    public function mus_list(Request $request)
    {
        $columns = array(
            0 => 'id'
        );

        $totalData = MusHeader::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika ada get pada pencarian beban_id
        if (empty($request->input('search.value'))) {
            $lists = MusHeader::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        }
        else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $lists = MusHeader::where('trans_no','LIKE', "%{$search}%")
                                    ->orWhere('request_by','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = MusHeader::where('trans_no','LIKE', "%{$search}%")
                                    ->orWhere('request_by','LIKE',"%{$search}%")
                                    ->count();
        }
        //collection data here
        $data = array();
        $no = 1;
        if (!empty($lists)) {
            foreach ($lists as $ro) {
                $edit = route('edit_mus', $ro->id);
                $show = route('show_mus', $ro->id);
                $delete = route('delete_mus', $ro->id);
                $row['no'] = $no;
                $row['trans_no'] = $ro->trans_no;
                $row['request_by'] = $ro->request_by;
                $row['tanggal'] = $ro->tanggal;
                $row['remarks'] = $ro->remarks;
                $row['action'] = " 
                    <a href=' $edit ' class='btn btn-warning btn-xs'><span class='glyphicon glyphicon-pencil'></span></a>
                    <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
                    ";
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
    } // end of so_list

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $data['uom'] = MasterUom::all();
        $data['master_baja'] = MasterStock::all();
        $data['vendor'] = MasterVendor::all();
        $data['mus_header'] = MusHeader::find($id);
        $data['mus_details'] = MusDetails::where('mus_header',$id)->get();
        $data['id'] = $id;
        return view('mus.show',$data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $data['uom'] = MasterUom::all();
        $data['master_baja'] = MasterStock::all();
        $data['vendor'] = MasterVendor::all();
        $data['mus_header'] = MusHeader::find($id);
        $data['mus_details'] = MusDetails::where('mus_header',$id)->get();
        $data['id'] = $id;
        return view('mus.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        //
        $request->validate([
            'tanggal'=>'required',
            'nama_pembeli'=>'required'
        ]);

        // validasi item
        $px_nama = "item_name_";
        $px_desc = "item_desc_";
        $px_qty = "qty_item_";
        $px_item_id = "item_id_";
        $dataitem_validate = array();
        for ($i=1; $i <= 20 ; $i++) { 
            if ($request->has($px_nama.$i)) {
                $dataitem_validate += 
                [
                    $px_desc.$i => 'required',
                    $px_qty.$i => 'required|regex:/^[0-9]+$/',
                ];
            } // end if
        } // end for
        $request->validate($dataitem_validate);
        // store header
        $header = MusHeader::find($id);
        $header->tanggal = $request->tanggal;
        $header->trans_no = $request->trans_no;
        $header->request_by = $request->nama_pembeli;
        $header->remarks = $request->desc;
        $header->updated_by = Auth::id();
        $header->updated_at = Carbon::now()->toDateTimeString();
        $simpan_header = $header->save();
        // store details
        if ($simpan_header) {
            // looping details barang
            for ($i=1; $i <= 20 ; $i++) { 
                if ($request->has($px_nama.$i)) {
                    // jika data baru
                    if ($request->input($px_item_id.$i) == "new_data" ) {
                        $data_details = [
                            'mus_header' => $header->id,
                            'trans_no' => $request->trans_no,
                            'id_barang' => $request->input($px_nama.$i),
                            'qty' => $request->input($px_qty.$i),
                            'desc' => $request->input($px_desc.$i),
                            'updated_by' => Auth::id(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ];
                        $details = New MusDetails();
                        $save_details = $details->create($data_details);
                        // jika berhasil save details maka update is taken dan field di tables stock inventory
                        if ($save_details) {
                            for ($s=1; $s <= $request->input($px_qty.$i); $s++) { 
                                DB::table('stock_inventory')
                                ->where('id_barang', $request->input($px_nama.$i))
                                ->where('is_taken', 0)
                                ->orderBy('id','asc')
                                ->limit(1)
                                ->update(
                                    [
                                        'is_taken' => 1,
                                        'taken_by' => $request->nama_pembeli,
                                        'no_sales_order' => $request->trans_no,
                                        'updated_by' => Auth::id(),
                                        'updated_at' => Carbon::now()->toDateTimeString()
                                    ]
                                );
                            }
                        } // enf if update stock inventory
                    }
                    // jika data lama
                    else {
                        $cek_details = MusDetails::where('id_barang',$request->input($px_nama.$i))->where('trans_no',$request->trans_no)->get(['qty']);
                        if ($request->input($px_qty.$i) != $cek_details[0]->qty) {
                            // balikan datanya menjadi default
                            $restore_data = DB::table('stock_inventory')
                                ->where('id_barang', $request->input($px_nama.$i))
                                ->where('is_taken', 1)->where('no_sales_order',$request->trans_no)
                                ->update(
                                    [
                                        'is_taken' => 0,
                                        'taken_by' => "(NULL)",
                                        'no_sales_order' => "(NULL)",
                                        'updated_by' => Auth::id(),
                                        'updated_at' => Carbon::now()->toDateTimeString()
                                    ]
                                );
                                if ($restore_data) {
                                    for ($s=1; $s <= $request->input($px_qty.$i); $s++) { 
                                        DB::table('stock_inventory')
                                        ->where('id_barang', $request->input($px_nama.$i))
                                        ->where('is_taken', 0)
                                        ->orderBy('id','asc')
                                        ->limit(1)
                                        ->update(
                                            [
                                                'is_taken' => 1,
                                                'taken_by' => $request->nama_pembeli,
                                                'no_sales_order' => $request->trans_no,
                                                'updated_by' => Auth::id(),
                                                'updated_at' => Carbon::now()->toDateTimeString()
                                            ]
                                        );
                                    } // end for looping qty
                                         
                                } // end if restore data
                        }
                        $data_details = [
                            'mus_header' => $header->id,
                            'trans_no' => $request->trans_no,
                            'id_barang' => $request->input($px_nama.$i),
                            'qty' => $request->input($px_qty.$i),
                            'desc' => $request->input($px_desc.$i),
                            'updated_by' => Auth::id(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ];
                        // restore yang istaken kembali menjadi default
                        // lalu update ulang
                        $update_details = MusDetails::find($request->input($px_item_id.$i))->update($data_details);
                    }
                    
                    
                } // end if
            } // end for loop
            if ($update_details || $save_details) {
                return response()->json([
                    'status' => true,
                    'message' => 'data pengambailan barang berhasil di perbarui'
                ], 202);    
            }
            
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $header = MusHeader::find($id);
        $header->deleted_by = Auth::id();
        $header->deleted_at = Carbon::now()->toDateTimeString();
        if($header->save()){
            $details = [
                'deleted_by' => Auth::id(),
                'deleted_at' => Carbon::now()->toDateTimeString()
            ];
            MusDetails::where('mus_header',$id)->update($details);
            return response()->json([
                'success' => 'Transaksi berhasil dihapus'
            ]);
        }
        //
    }

    public function barang_prop($id)
    {
        // $data = MasterStock::where('id_barang',$id);
        $data = StockInventory::select(DB::raw('count(stock_inventory.is_taken) as current_stock'))
            ->where('stock_inventory.is_taken','0')->where('stock_inventory.id_barang',$id)->groupBy('stock_inventory.id_barang')->get();
        return $data;
    }

    // remove item list
    public function removeItem(Request $request)
    {
        $id = $request->id_header;
        $header = MusHeader::find($id);
        $header->updated_by = Auth::id();
        $header->updated_at = Carbon::now()->toDateTimeString();
        if($header->save()){
            $details = [
                'deleted_by' => Auth::id(),
                'deleted_at' => Carbon::now()->toDateTimeString()
            ];
            $qty = MusDetails::where('id',$request->id_details)->where('mus_header',$id)->get();
            $restore_data = DB::table('stock_inventory')
                                ->where('id_barang', $qty[0]->id_barang)
                                ->where('is_taken', 1)->where('no_sales_order',$qty[0]->trans_no)
                                ->update(
                                    [
                                        'is_taken' => 0,
                                        'taken_by' => "(NULL)",
                                        'no_sales_order' => "(NULL)",
                                        'updated_by' => Auth::id(),
                                        'updated_at' => Carbon::now()->toDateTimeString()
                                    ]
                                );
            $remove_details = MusDetails::where('id',$request->id_details)->where('mus_header',$id)->update($details);

            if ($remove_details) {
                return response()->json([
                    'status' => true,
                    'message' => 'Item berhasil dihapus'
                ], 200);
            }
        }
    }
}
