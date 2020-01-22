<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\StockInventory;
use App\SalesModel;
use App\SalesDetailsModel;
use App\MasterUom;
use App\MasterStock;
use App\MasterVendor;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return view('sales.index');
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
        $code = "INV-";
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');

        $rn_a = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
            $data['trans_no'] = $code.$tahun.$bulan.sprintf('%04d', $rn[0]->rn+1);
        }
        // end of running number
        return view('sales.create',$data);
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
            'nsfp'=>'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'bukti_bayar' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'status'=>'required'
        ]);

        // validasi item
        $px_nama = "item_name_";
        $px_desc = "item_desc_";
        $px_qty = "qty_item_";
        $px_ppn = "ppn_item_";
        $px_price = "harga_satuan_item_";
        $px_price_total = "harga_total_item_";
        $px_ppn = "ppn_item_";
        $px_pph23 = "pph23_item_";
        $px_pph4 = "pph4_item_";
        $px_discount = "diskon_item_";
        $dataitem_validate = array();
        for ($i=1; $i <= 20 ; $i++) { 
            if ($request->has($px_nama.$i)) {
                $dataitem_validate += 
                [
                    $px_desc.$i => 'required',
                    $px_qty.$i => 'required|regex:/^[0-9]+$/',
                    $px_price.$i => 'required',
                ];
            } // end if
        } // end for
        $request->validate($dataitem_validate);
        // store header
        $header = New SalesModel();
        $header->tanggal = $request->tanggal;
        $header->no_faktur_pajak = $request->nsfp;
        $header->no_invoice = $request->trans_no;
        $header->po_number = $request->po_number;
        $header->baja = $request->tipe;
        $header->customer_id = $request->nama_pembeli;
        $header->jumlah = $request->total;
        $header->tanggal_bayar = $request->tanggal_bayar;
        $header->status = $request->status;
        $header->remarks = $request->desc;
        $header->created_by = Auth::id();
        $header->created_at = Carbon::now()->toDateTimeString();
        $header->remarks = $request->desc;
        if($request->hasFile('file')){
            $request->file('file')->move('uploads/so/po/',$request->file('file')->getClientOriginalName());
            $header->bukti_po = $request->file('file')->getClientOriginalName();
        }
        if($request->hasFile('bukti_bayar')){
            $request->file('bukti_bayar')->move('uploads/so/bb/',$request->file('bukti_bayar')->getClientOriginalName());
            $header->bukti_bayar = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $simpan_header = $header->save();
        // store details
        if ($simpan_header) {
            // looping details barang
            for ($i=1; $i <= 20 ; $i++) { 
                if ($request->has($px_nama.$i)) {
                    $data_details = [
                        'so_header' => $header->id,
                        'no_invoice' => $request->trans_no,
                        'id_barang' => $request->input($px_nama.$i),
                        'qty' => $request->input($px_qty.$i),
                        'harga_satuan' => $request->input($px_price.$i),
                        'jumlah' => $request->input($px_price_total.$i),
                        'ppn' => $request->input($px_ppn.$i),
                        'pph_23' => $request->input($px_pph23.$i),
                        'pph_4' => $request->input($px_pph4.$i),
                        'discount' => $request->input($px_discount.$i),
                        'desc' => $request->input($px_desc.$i),
                        'created_by' => Auth::id(),
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ];
                    $details = New SalesDetailsModel();
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
                    'message' => 'data penjualan berhasil di simpan'
                ], 202);    
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'data penjualan gagal di simpan'
                ], 404);    
            }
        }
        
    }

    public function so_list(Request $request)
    {
        $columns = array(
            0 => 'id'
        );

        $totalData = SalesModel::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika ada get pada pencarian beban_id
        if (empty($request->input('search.value'))) {
            $lists = SalesModel::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        }
        else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $lists = SalesModel::where('no_invoice','LIKE', "%{$search}%")
                                    ->orWhere('po_number','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = SalesModel::where('no_invoice','LIKE', "%{$search}%")
                                    ->orWhere('po_number','LIKE',"%{$search}%")
                                    ->count();
        }
        //collection data here
        $data = array();
        $no = 1;
        if (!empty($lists)) {
            foreach ($lists as $ro) {
                $edit = route('edit_so', $ro->id);
                $show = route('show_so', $ro->id);
                $delete = route('delete_so', $ro->id);
                $row['no'] = $no;
                $row['no_invoice'] = $ro->no_invoice;
                $row['tanggal'] = $ro->tanggal;
                $row['po_number'] = $ro->po_number;
                $row['jumlah'] = "Rp. ".number_format($ro->jumlah,2,',',',');
                $row['bukti_bayar'] = "<a target='_blank' href=".url('uploads/file_service_kendaraan/' .$ro->bukti_bayar).">"."Bukti Bayar</a>";
                if ($ro->status == "LUNAS") {
                    $row['status'] = "<span class='label label-success'> LUNAS</span>";
                } else if ($ro->status == "DP") {
                    $row['status'] = "<span class='label label-danger'> BELUM LUNAS</span>";
                };
                $row['remarks'] = $ro->remarks;
                if ($ro->status == "LUNAS") {
                    $row['action'] = " 
                    <a href=' $show ' class='btn btn-success btn-xs'><span class='fa fa-eye'></span></a>
                    ";
                }
                else {
                    $row['action'] = " 
                    <a href=' $edit ' class='btn btn-warning btn-xs'><span class='glyphicon glyphicon-pencil'></span></a>
                    <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
                    ";
                }
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
        $data['pr_header'] = PurchaseRequestModel::find($id);
        $data['pr_details'] = PurchaseRequestDetailsModel::where('pr_header',$id)->get();
        $data['id'] = $id;
        return view('pr.edit',$data);
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
        $data['so_header'] = SalesModel::find($id);
        $data['so_details'] = SalesDetailsModel::where('so_header',$id)->get();
        $data['id'] = $id;
        return view('sales.edit',$data);
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
            'nsfp'=>'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'bukti_bayar' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'status'=>'required'
        ]);

        // validasi item
        $px_nama = "item_name_";
        $px_desc = "item_desc_";
        $px_qty = "qty_item_";
        $px_ppn = "ppn_item_";
        $px_price = "harga_satuan_item_";
        $px_price_total = "harga_total_item_";
        $px_ppn = "ppn_item_";
        $px_pph23 = "pph23_item_";
        $px_pph4 = "pph4_item_";
        $px_discount = "diskon_item_";
        $px_item_id = "item_id_";
        $dataitem_validate = array();
        for ($i=1; $i <= 20 ; $i++) { 
            if ($request->has($px_nama.$i)) {
                $dataitem_validate += 
                [
                    $px_desc.$i => 'required',
                    $px_qty.$i => 'required|regex:/^[0-9]+$/',
                    $px_price.$i => 'required',
                ];
            } // end if
        } // end for
        $request->validate($dataitem_validate);
        // store header
        $header = SalesModel::find($id);
        $header->tanggal = $request->tanggal;
        $header->no_faktur_pajak = $request->nsfp;
        $header->no_invoice = $request->trans_no;
        $header->po_number = $request->po_number;
        $header->baja = $request->tipe;
        $header->customer_id = $request->nama_pembeli;
        $header->jumlah = $request->total;
        $header->tanggal_bayar = $request->tanggal_bayar;
        $header->status = $request->status;
        $header->remarks = $request->desc;
        $header->updated_by = Auth::id();
        $header->updated_at = Carbon::now()->toDateTimeString();
        $header->remarks = $request->desc;
        if($request->hasFile('file')){
            Storage::delete('uploads/so/po/'.$emp->bukti_po);
            $request->file('file')->move('uploads/so/po/',$request->file('file')->getClientOriginalName());
            $header->bukti_po = $request->file('file')->getClientOriginalName();
        }
        if($request->hasFile('bukti_bayar')){
            Storage::delete('uploads/so/bb'.$emp->bukti_bayar);
            $request->file('bukti_bayar')->move('uploads/so/bb/',$request->file('bukti_bayar')->getClientOriginalName());
            $header->bukti_bayar = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $simpan_header = $header->save();
        // store details
        if ($simpan_header) {
            // looping details barang
            for ($i=1; $i <= 20 ; $i++) { 
                if ($request->has($px_nama.$i)) {
                    // jika data baru
                    if ($request->input($px_item_id.$i) == "new_data" ) {
                        $data_details = [
                            'so_header' => $header->id,
                            'no_invoice' => $request->trans_no,
                            'id_barang' => $request->input($px_nama.$i),
                            'qty' => $request->input($px_qty.$i),
                            'harga_satuan' => $request->input($px_price.$i),
                            'jumlah' => $request->input($px_price_total.$i),
                            'ppn' => $request->input($px_ppn.$i),
                            'pph_23' => $request->input($px_pph23.$i),
                            'pph_4' => $request->input($px_pph4.$i),
                            'discount' => $request->input($px_discount.$i),
                            'desc' => $request->input($px_desc.$i),
                            'created_by' => Auth::id(),
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ];
                        $details = New SalesDetailsModel();
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
                        $data_details = [
                            'so_header' => $header->id,
                            'no_invoice' => $request->trans_no,
                            'id_barang' => $request->input($px_nama.$i),
                            'qty' => $request->input($px_qty.$i),
                            'harga_satuan' => $request->input($px_price.$i),
                            'jumlah' => $request->input($px_price_total.$i),
                            'ppn' => $request->input($px_ppn.$i),
                            'pph_23' => $request->input($px_pph23.$i),
                            'pph_4' => $request->input($px_pph4.$i),
                            'discount' => $request->input($px_discount.$i),
                            'desc' => $request->input($px_desc.$i),
                            'updated_by' => Auth::id(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ];
                        // restore yang istaken kembali menjadi default
                        // lalu update ulang
                        $update_details = SalesDetailsModel::find($request->input($px_item_id.$i))->update($data_details);
                        $cek_details = SalesDetailsModel::where('id_barang',$request->input($px_nama.$i))->where('no_invoice',$request->trans_no)->get(['qty']);
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

                    }
                    
                    
                } // end if
            } // end for loop
            if ($save_details) {
                return response()->json([
                    'status' => true,
                    'message' => 'data penjualan berhasil di simpan'
                ], 202);    
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'data penjualan gagal di simpan'
                ], 404);    
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
        $header = SalesModel::find($id);
        $header->deleted_by = Auth::id();
        $header->deleted_at = Carbon::now()->toDateTimeString();
        if($header->save()){
            $details = [
                'deleted_by' => Auth::id(),
                'deleted_at' => Carbon::now()->toDateTimeString()
            ];
            SalesDetailsModel::where('so_header',$id)->update($details);
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
}
