<?php

namespace App\Http\Controllers;
use App\RequestTransaction;
use \App\Vendor;
use \App\Category;
use \App\Beban;
use \App\Employee;
use \App\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReqTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //    
        $data['emp'] = Employee::all();
        $data['vn'] = Vendor::all();
        $data['trans'] = Beban::all();
        $data['category'] = Category::all();
        $data['rr'] = RequestTransaction::all();
        return view('request/index')->with($data);
    }
    public function multilist(Request $request){
        $columns = array(
            0 => 'id',
            1 => 'id',
            2 => 'trans_no',
            3 => 'request_by',
            4 => 'beban_id',
            5 => 'vendor_id',
            6 => 'category_id',
            7 => 'keterangan',
            8 => 'qty',
            9 => 'qty_satuan',
            10 => 'harga_satuan',
            11 => 'harga_total',
            12 => 'finance_id',
            13 => 'file',
            14 => 'bukti_bayar',
            15 => 'tanggal_bayar',
        );
        $totalData = RequestTransaction::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            $multi = RequestTransaction::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $multi = RequestTransaction::where('vendor_id','LIKE', "%{$search}%")
                                    ->orWhere('category_id','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = RequestTransaction::where('vendor_id','LIKE', "%{$search}%")
                                        ->orWhere('category_id','LIKE',"%{$search}%")
                                        ->count();
        }
        // custom filter query here
        if (!empty($request->input('filter_beban'))) {
            $multi = RequestTransaction::where('beban_id',"$request->filter_beban")
                                        ->offset($start)
                                        ->limit($limit)
                                        ->orderBy($order, $dir)
                                        ->get();

            $totalFiltered = RequestTransaction::where('beban_id', "$request->filter_beban")
                                                ->count();
        }
         //collection data here
         $data = array();
         $no = 1;
        //  $id_rn = DB::table('request_transaction')->orderBy('id', 'desc')->first();
        //  $rn = $id_rn->id;
        //  $bulan = Carbon::now()->format('m');
        //  $tahun = Carbon::now()->format('Y');
         
         if (!empty($multi)) {
             foreach ($multi as $m) {
                 $edit = route('editrequest', $m->id);
                 $delete = route('deletedrequest', $m->id);
                 $row['no'] = $no;
                 $row['trans_no'] =$m->trans_no;
                 $row['tanggal_bayar'] = $m->tanggal_bayar;
                 $row['request_by'] = $m->employ->nama;
                 $row['file'] = "<a href=".url('uploads/invoice/' .$m->file).">"."File Invoice";
                 $row['bukti_bayar'] = "<a href=".url('uploads/bukti_bayar/' .$m->bukti_bayar).">"." Pembayaran";
                 $row['beban_id'] = $m->requestbeban->nama?? '-';;
                 $row['vendor_id'] = $m->ven->nama?? '-';;
                 $row['category_id'] = $m->listcategory->name;
                 $row['keterangan'] = $m->keterangan;
                 $row['qty'] = $m->qty;
                 $row['qty_satuan'] = $m->qty_satuan;
                 $row['harga_satuan'] = "Rp " . number_format($m->harga_satuan,2,',','.');
                 $row['harga_total'] = "Rp " . number_format($m->harga_total,2,',','.');
                 $row['finance_id'] = $m->finance->nama;
                 if ($m->status == "APPROVED") {
                    $row['status'] = "<span class='label label-success'> APPROVED</span>";
                } else if ($m->status == "REJECTED") {
                    $row['status'] = "<span class='label label-danger'> REJECTED</span>";
                } else if ($m->status == "CANCEL") {
                    $row['status'] = "<span class='label label-warning'> CANCEL</span>";
                } else if ($m->status == "PAID"){
                    $row['status'] = "<span class='label label-primary'> PAID</span>";
                };
                //  $row['status'] = $m->status == "Approved" ? "<span class='label label-success'> Approved</span>" : "<span class='label label-danger'> Rejected </span> ";
                 $row['options'] = " 
                 <a href=' $edit ' class='btn btn-warning btn-xs'><span class='glyphicon glyphicon-pencil'></span></a>
                 <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
                 ";
                 
                 $data[] = $row;
                 $no++;
             }
             // return $data;
             
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $data['category'] = Category::all();
        $data['trans'] = Beban::all();
        $data['emp'] = Employee::all();
        $data['vn'] = Vendor::all();
        return view('request/create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $error = $request->validate([
            'trans_no'=>'required',
            'request_by'=>'required',
            'beban_id'=>'required',
            'vendor_id'=>'required',
            'qty'=>'required',
            'qty_satuan'=>'required',
            'harga_satuan'=>'required',
            'finance_id'=>'required',
            
        ]);

        $data = new RequestTransaction();
        $running_number = RequestTransaction::latest()->first()->id +1;
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');
        $number = "MR"."/".sprintf('%04d', $running_number).$request->trans_no.$bulan."/".$tahun;
        $data->trans_no = $number;
        $data->request_by = $request->request_by;
        $data->beban_id = $request->beban_id;
        $data->vendor_id = $request->vendor_id;
        $data->category_id = $request->category_id;
        if($request->hasFile('file')){
            $request->file('file')->move('uploads/invoice/',$request->file('file')->getClientOriginalName());
            $data->file = $request->file('file')->getClientOriginalName();
        }
        if($request->hasFile('bukti_bayar')){
            $request->file('bukti_bayar')->move('uploads/bukti_bayar/',$request->file('bukti_bayar')->getClientOriginalName());
            $data->bukti_bayar = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $data->keterangan=$request->get('keterangan');
        $data->qty=$request->get('qty');
        $data->tanggal_bayar=$request->get('tanggal_bayar');
        $data->qty_satuan=$request->get('qty_satuan');
        $data->harga_satuan=$request->get('harga_satuan'); 
        $data->harga_total=$request->get('harga_total'); 
        $data->finance_id=$request->finance_id; 
        $data->status=$request->get('status'); 
        $data->created_by = Auth::id();
        $data->created_at = Carbon::now()->toDateTimeString();
        $data->save();
        $lastid = DB::table('request_transaction')->orderBy('id', 'desc')->first();
        $logid = $lastid->id;
        $save_log= New TransactionLog();
        $datalog = 
            [
                'transaction_id' => $logid,
                'status' => $request->status,
                'reason' => $request->keterangan,
                'created_at' => Carbon::now()->toDateTimeString(),
                'created_by' => Auth::id(),
            ];
            $save = $save_log->create($datalog);
        return redirect('/request')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(RequestTransaction $data_request,$id)
    {
        //
        $data["dd"] = $data_request::find($id);
        $data['category'] = Category::all();
        $data['trans'] = Beban::all();
        $data['emp'] = Employee::all();
        $data['vn'] = Vendor::all();
        return view('request/edit')->with($data);
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
        $error = $request->validate([
            
            'request_by'=>'required',
            'beban_id'=>'required',
            'vendor_id'=>'required',
            'qty'=>'required',
            'qty_satuan'=>'required',
            'harga_satuan'=>'required',
            'finance_id'=>'required'
            
            
        ]);
        $data = RequestTransaction::find($id);
        $running_number = RequestTransaction::latest()->first()->id ;
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');
        if($request->trans_no){
            $number = "MR"."/".sprintf('%04d', $running_number).$request->trans_no.$bulan."/".$tahun;
            $data->trans_no = $number;
        }
       
        $data->request_by = $request->get('request_by');
        $data->beban_id = $request->get('beban_id');
        $data->vendor_id = $request->get('vendor_id');
        $data->category_id = $request->get('category_id');
        if($request->hasFile('file')){
            $request->file('file')->move('uploads/invoice/',$request->file('file')->getClientOriginalName());
            $data->file = $request->file('file')->getClientOriginalName();
        }
        if($request->hasFile('bukti_bayar')){
            $request->file('bukti_bayar')->move('uploads/bukti_bayar/',$request->file('bukti_bayar')->getClientOriginalName());
            $data->bukti_bayar = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $data->keterangan=$request->get('keterangan');
        $data->qty=$request->get('qty');
        $data->tanggal_bayar=$request->get('tanggal_bayar'); 
        $data->qty_satuan=$request->get('qty_satuan');
        $data->harga_satuan=$request->get('harga_satuan'); 
        $data->harga_total=$request->get('harga_total'); 
        $data->finance_id=$request->get('finance_id'); 
        $data->status=$request->get('status'); 
        $data->updated_by = Auth::id();
        $data->updated_at = Carbon::now()->toDateTimeString();
        $data->save();
        $lastid = DB::table('request_transaction')->orderBy('id', 'desc')->first();
        $logid = $lastid->id;
        $save_log= New TransactionLog();
        $datalog = 
            [
                'transaction_id' => $logid,
                'status' => $request->status,
                'reason' => $request->keterangan,
                'updated_at' => Carbon::now()->toDateTimeString(),
                'updated_by' => Auth::id(),
            ];
            $save = $save_log->create($datalog);
        return redirect('/request')->with('success', 'Data berhasil di Update');

        //kirim data ke log
        

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $trans= RequestTransaction::find($id);
        $trans->deleted_by = Auth::id();
        $trans->deleted_at = Carbon::now()->toDateTimeString();
        if($trans->save()){
            return response()->json([
                'success' => 'Beban berhasil dihapus'
            ]);
        }
    }
}
