<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PurchaseRequestDetailsModel;
use App\PurchaseRequestModel;
use App\MasterUom;
use App\MasterVendor;
use App\ValueFromUser;
use App\MasterStock;
use App\StockInventory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseRequestController extends Controller
{
    //

    public function index()
    {
        return view('pr.index');
    } // end of index

    public function create()
    {
        $data['uom'] = MasterUom::all();
        $data['master_baja'] = MasterStock::all();
        $data['vendor'] = MasterVendor::all();
        // running number
        $code = "/PR/";
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');
        switch ($bulan) {
            case '1':
            $bulan = 'I';
            break;
            case '2':
            $bulan = 'II';
            break;
            case '3':
            $bulan = 'III';
            break;
            case '4':
            $bulan = 'IV';
            break;
            case '5':
            $bulan = 'V';
            break;
            case '6':
            $bulan = 'VI';
            break;
            case '7':
            $bulan = 'VII';
            break;
            case '8':
            $bulan = 'VIII';
            break;
            case '9':
            $bulan = 'IX';
            break;
            case '10':
            $bulan = 'X';
            break;
            case '11':
            $bulan = 'XI';
            break;
            case '12':
            $bulan = 'XII';
            break;
        }
        $rn_a = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
            $data['trans_no'] = sprintf('%04d', $rn[0]->rn+1).$code.$bulan."/".$tahun;
        }
        // end of running number
        return view('pr.create',$data);
        
    } // end of create

    public function store(Request $request)
    {
         //return $request->all();
         $request->validate([
            'tanggal'=>'required',
            'nsfp'=>'required',
            'invoice_number'=>'required',
            'kurs'=>'required',
            'nilai_kurs'=>'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'bukti_bayar' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'status'=>'required'
        ]);

        // validasi item
        $px_nama = "item_name_";
        $px_desc = "item_desc_";
        $px_qty = "qty_item_";
        $px_ppn = "ppn_item_";
        $px_qty_uom = "qty_satuan_item_";
        $px_price = "harga_satuan_item_";
        $px_price_total = "harga_total_item_";
        $dataitem_validate = array();
        for ($i=1; $i <= 20 ; $i++) { 
            if ($request->has($px_nama.$i)) {
                $dataitem_validate += 
                [
                    $px_nama.$i => 'required',
                    $px_desc.$i => 'required',
                    $px_qty.$i => 'required|regex:/^[0-9]+$/',
                    $px_qty_uom.$i => 'required',
                    $px_price.$i => 'required',
                ];
            } // end if
        } // end for
        $request->validate($dataitem_validate);

        // if($error){
        $pr = new PurchaseRequestModel();
        $pr->pr_number = $request->trans_no;
        $pr->tanggal = $request->tanggal;
        $pr->nsfp = $request->nsfp;
        $pr->status = $request->status;
        $pr->invoice_number = $request->invoice_number;
        $pr->po_number = $request->po_number;
        $pr->vendor_id = $request->vendor_id;
        if($request->hasFile('file')){
            $request->file('file')->move('uploads/pr/invoice/',$request->file('file')->getClientOriginalName());
            $pr->file = $request->file('file')->getClientOriginalName();
        }
        $pr->kurs_kode = $request->kurs;
        $pr->nilai_kurs = $request->nilai_kurs;
        $pr->total_price = $request->total;
        $pr->paid_date = $request->tanggal_bayar;
        if($request->hasFile('bukti_bayar')){
            $request->file('bukti_bayar')->move('uploads/pr/bb/',$request->file('bukti_bayar')->getClientOriginalName());
            $pr->paid_date_file = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $pr->remarks = $request->desc;
        $pr->created_by = Auth::id();
        $pr->updated_by = Auth::id();
        $pr->created_at = Carbon::now()->toDateTimeString();
        $save_header = $pr->save();
        $lastid = $pr->id;
            //jika berhasil save
            if($save_header){
                for ($i=1; $i <= 20 ; $i++) { 
                    if ($request->has($px_nama.$i)) {
                        $id_pembelian = Carbon::now()->format('ymd').'_'.str_pad(rand(0,999), 3, "0", STR_PAD_LEFT); // yymmdd_rand(4)
                        $dataitem = 
                        [
                            'pr_header' => $lastid,
                            'pr_number' => $request->trans_no,
                            'id_pembelian' => $id_pembelian,
                            'ppn' => $request->input($px_ppn.$i),
                            'id_barang' => $request->input($px_nama.$i),
                            'qty' => $request->input($px_qty.$i),
                            'qty_uom' => $request->input($px_qty_uom.$i),
                            'qty_price' => $request->input($px_price.$i),
                            'sub_total' => $request->input($px_price_total.$i),
                            'desc' => $request->input($px_desc.$i),
                            'created_at' => Carbon::now()->toDateTimeString(),
                            'created_by' => Auth::id(),
                        ];
                        // jika status paid maka langung masuk ke stock
                        if ($request->status == "PAID") {
                            # code...
                            for ($a=1; $a <= $request->input($px_qty.$i); $a++) { 
                                # code...
                                $datainventory = 
                                [
                                    'pr_header' => $lastid,
                                    'id_barang' => $request->input($px_nama.$i),
                                    'id_pembelian' => $id_pembelian, // yymmdd_rand(4)
                                    'created_at' => Carbon::now()->toDateTimeString(),
                                    'created_by' => Auth::id(),
                                ];
                                $stock_inventory = new StockInventory();
                                $save_inv = $stock_inventory->create($datainventory);
                            }
                        }
                        
                        // validasi jika inputan sudah ada atau tidak ada maka input ke ValueFromUser
                        $user_def = ValueFromUser::where('value', $request->input($px_nama.$i))->count(); // 0 tidak ada data, 1 ada data
                        if ($user_def != 1) {
                            $user_def_new = new ValueFromUser;
                            $user_def_new->value = $request->input($px_nama.$i);
                            $user_def_new->group = "PR";
                            $user_def_new->created_by = Auth::id();
                            $user_def_new->save();
                        }

                        $pr_details = new PurchaseRequestDetailsModel();
                        $save = $pr_details->create($dataitem);
                    } // end if
                } // end for
            } // end if save
        return response()->json([
            'status' => true,
            'message' => 'Transaksi Berhasil Di Simpan'
        ], 200);
    }

    public function pr_list(Request $request)
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

        // jika ada get pada pencarian beban_id
        if (empty($request->input('search.value'))) {
            $lists = PurchaseRequestModel::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        }
        else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $lists = PurchaseRequestModel::where('pr_number','LIKE', "%{$search}%")
                                    ->orWhere('po_number','LIKE',"%{$search}%")
                                    ->orWhere('invoice_number','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = PurchaseRequestModel::where('pr_number','LIKE', "%{$search}%")
                                    ->orWhere('po_number','LIKE',"%{$search}%")
                                    ->orWhere('invoice_number','LIKE',"%{$search}%")
                                    ->count();
        }
        //collection data here
        $data = array();
        $no = 1;
        if (!empty($lists)) {
            foreach ($lists as $ro) {
                $edit = route('edit_pr', $ro->id);
                $show = route('show_pr', $ro->id);
                $delete = route('delete_pr', $ro->id);
                $row['no'] = $no;
                $row['pr_number'] = $ro->pr_number;
                $row['tanggal'] = $ro->tanggal;
                $row['invoice_number'] = $ro->invoice_number;
                $row['total_price'] = "Rp. ".number_format($ro->total_price,2,',',',');
                $row['paid_date_file'] = "<a target='_blank' href=".url('uploads/file_service_kendaraan/' .$ro->paid_date_file).">"."Bukti Bayar</a>";
                if ($ro->status == "APPROVED") {
                    $row['status'] = "<span class='label label-success'> APPROVED</span>";
                } else if ($ro->status == "REJECTED") {
                    $row['status'] = "<span class='label label-danger'> REJECTED</span>";
                } else if ($ro->status == "CANCEL") {
                    $row['status'] = "<span class='label label-warning'> CANCEL</span>";
                } else if ($ro->status == "PAID"){
                    $row['status'] = "<span class='label label-primary'> PAID</span>";
                };
                $row['remarks'] = $ro->remarks;
                if ($ro->status == "PAID") {
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
    } // end of pr_list

    public function autocomplete_item_name(Request $request)
    {
        $data = ValueFromUser::select('value')->where("value","like","%{$request->input('query')}%")->get();
        return response()->json($data);
    } // end of autocomplete item

    public function edit(Request $request, $id)
    {
        $data['uom'] = MasterUom::all();
        $data['master_baja'] = MasterStock::all();
        $data['vendor'] = MasterVendor::all();
        $data['pr_header'] = PurchaseRequestModel::find($id);
        $data['pr_details'] = PurchaseRequestDetailsModel::where('pr_header',$id)->get();
        $data['id'] = $id;
        return view('pr.edit',$data);
    }

    public function show(Request $request, $id)
    {
        $data['uom'] = MasterUom::all();
        $data['master_baja'] = MasterStock::all();
        $data['vendor'] = MasterVendor::all();
        $data['pr_header'] = PurchaseRequestModel::find($id);
        $data['pr_details'] = PurchaseRequestDetailsModel::where('pr_header',$id)->get();
        $data['id'] = $id;
        return view('pr.show',$data);
    }

    public function update(Request $request, $id)
    {
        //return $request->all();
        $request->validate([
            'tanggal'=>'required',
            'nsfp'=>'required',
            'invoice_number'=>'required',
            'kurs'=>'required',
            'nilai_kurs'=>'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'bukti_bayar' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            'status'=>'required'
        ]);

        // validasi item
        $px_nama = "item_name_";
        $px_desc = "item_desc_";
        $px_qty = "qty_item_";
        $px_ppn = "ppn_item_";
        $px_qty_uom = "qty_satuan_item_";
        $px_price = "harga_satuan_item_";
        $px_price_total = "harga_total_item_";
        $px_item_id = "item_id_";
        $dataitem_validate = array();
        for ($i=1; $i <= 20 ; $i++) { 
            if ($request->has($px_nama.$i)) {
                $dataitem_validate += 
                [
                    $px_nama.$i => 'required',
                    $px_desc.$i => 'required',
                    $px_qty.$i => 'required|regex:/^[0-9]+$/',
                    $px_qty_uom.$i => 'required',
                    $px_price.$i => 'required',
                ];
            } // end if
        } // end for
        $request->validate($dataitem_validate);

        $pr = PurchaseRequestModel::find($id);
        $pr->pr_number = $request->trans_no;
        $pr->tanggal = $request->tanggal;
        $pr->nsfp = $request->nsfp;
        $pr->status = $request->status;
        $pr->invoice_number = $request->invoice_number;
        $pr->po_number = $request->po_number;
        $pr->vendor_id = $request->vendor_id;
        if($request->hasFile('file')){
            Storage::delete('uploads/pr/invoice'.$emp->file);
            $request->file('file')->move('uploads/pr/invoice/',$request->file('file')->getClientOriginalName());
            $pr->file = $request->file('file')->getClientOriginalName();
        }
        $pr->kurs_kode = $request->kurs;
        $pr->nilai_kurs = $request->nilai_kurs;
        $pr->total_price = $request->total;
        $pr->paid_date = $request->tanggal_bayar;
        if($request->hasFile('bukti_bayar')){
            Storage::delete('uploads/pr/bb'.$emp->paid_date_file);
            $request->file('bukti_bayar')->move('uploads/pr/bb/',$request->file('bukti_bayar')->getClientOriginalName());
            $pr->paid_date_file = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $pr->remarks = $request->desc;
        $pr->created_by = Auth::id();
        $pr->updated_by = Auth::id();
        $pr->created_at = Carbon::now()->toDateTimeString();
        $save_header = $pr->save();
        $lastid = $pr->id;

        // jika berhasil save header
        if ($save_header) {
            for ($i=1; $i <= 20 ; $i++) {
                // matching dengan nama form
                if ($request->has($px_nama.$i)) {
                // jika data baru
                        $id_pembelian = Carbon::now()->format('ymd').'_'.str_pad(rand(0,999), 3, "0", STR_PAD_LEFT); // yymmdd_rand(4)
                        if ($request->input($px_item_id.$i) == "new_data" ) {
                            $dataitem = 
                            [
                                'pr_header' => $lastid,
                                'pr_number' => $request->trans_no,
                                'id_pembelian' => $id_pembelian,
                                'ppn' => $request->input($px_ppn.$i),
                                'id_barang' => $request->input($px_nama.$i),
                                'qty' => $request->input($px_qty.$i),
                                'qty_uom' => $request->input($px_qty_uom.$i),
                                'qty_price' => $request->input($px_price.$i),
                                'sub_total' => $request->input($px_price_total.$i),
                                'desc' => $request->input($px_desc.$i),
                                'created_at' => Carbon::now()->toDateTimeString(),
                                'created_by' => Auth::id(),
                            ];
                            // jika status paid maka langung masuk ke stock
                            if ($request->status == "PAID") {
                                # code...
                                for ($a=1; $a <= $request->input($px_qty.$i); $a++) { 
                                    # code...
                                    $datainventory = 
                                    [
                                        'pr_header' => $lastid,
                                        'id_barang' => $request->input($px_nama.$i),
                                        'id_pembelian' => $id_pembelian, // yymmdd_rand(4)
                                        'created_at' => Carbon::now()->toDateTimeString(),
                                        'created_by' => Auth::id(),
                                    ];
                                    $stock_inventory = new StockInventory();
                                    $save_inv = $stock_inventory->create($datainventory);
                                }
                            }
                            
                            // validasi jika inputan sudah ada atau tidak ada maka input ke ValueFromUser
                            $user_def = ValueFromUser::where('value', $request->input($px_nama.$i))->count(); // 0 tidak ada data, 1 ada data
                            if ($user_def != 1) {
                                $user_def_new = new ValueFromUser;
                                $user_def_new->value = $request->input($px_nama.$i);
                                $user_def_new->group = "PR";
                                $user_def_new->created_by = Auth::id();
                                $user_def_new->save();
                            }
    
                            $pr_details = new PurchaseRequestDetailsModel();
                            $save = $pr_details->create($dataitem);
                            }
                        // update field yang lain
                        else {
                                $dataitem = 
                            [
                                'ppn' => $request->input($px_ppn.$i),
                                'id_barang' => $request->input($px_nama.$i),
                                'qty' => $request->input($px_qty.$i),
                                'qty_uom' => $request->input($px_qty_uom.$i),
                                'qty_price' => $request->input($px_price.$i),
                                'sub_total' => $request->input($px_price_total.$i),
                                'desc' => $request->input($px_desc.$i),
                                'created_at' => Carbon::now()->toDateTimeString(),
                                'created_by' => Auth::id(),
                            ];
                            PurchaseRequestDetailsModel::find($request->input($px_item_id.$i))->update($dataitem);
                                // jika status paid maka langung masuk ke stock
                            if ($request->status == "PAID") {
                                # code...
                                for ($a=1; $a <= $request->input($px_qty.$i); $a++) { 
                                    # code...
                                    $datainventory = 
                                    [
                                        'pr_header' => $lastid,
                                        'id_barang' => $request->input($px_nama.$i),
                                        'id_pembelian' => $id_pembelian, // yymmdd_rand(4)
                                        'created_at' => Carbon::now()->toDateTimeString(),
                                        'created_by' => Auth::id(),
                                    ];
                                    $stock_inventory = new StockInventory();
                                    $save_inv = $stock_inventory->create($datainventory);
                                }
                            }
                            
                            // validasi jika inputan sudah ada atau tidak ada maka input ke ValueFromUser
                            $user_def = ValueFromUser::where('value', $request->input($px_nama.$i))->count(); // 0 tidak ada data, 1 ada data
                            if ($user_def != 1) {
                                $user_def_new = new ValueFromUser;
                                $user_def_new->value = $request->input($px_nama.$i);
                                $user_def_new->group = "PR";
                                $user_def_new->created_by = Auth::id();
                                $user_def_new->save();
                            }
                                // $save = $savetransactionitem->create($dataitem);
                        }
                } // end of matching
                
            }
            // return $dataitem;
            return response()->json([
                'status' => true,
                'message' => 'Transaksi Berhasil Di Update'
            ], 200);
            
        }
    }

    public function destroy($id)
    {
        $header = PurchaseRequestModel::find($id);
        $header->deleted_by = Auth::id();
        $header->deleted_at = Carbon::now()->toDateTimeString();
        if($header->save()){
            $details = [
                'deleted_by' => Auth::id(),
                'deleted_at' => Carbon::now()->toDateTimeString()
            ];
            PurchaseRequestDetailsModel::where('pr_header',$id)->update($details);
            return response()->json([
                'success' => 'Transaksi berhasil dihapus'
            ]);
        }
    } // end of destroy

}  // end of controller