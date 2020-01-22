<?php

namespace App\Http\Controllers;
use App\Vehicle;
use App\Beban;
use App\Employee;
use \App\Category;
use App\Vendor;
use App\ServiceVehicle;
use App\ServiceTransLog;
use App\TransactionItem;
use App\Insurance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Appp\Http\Requests\SendRequest;
use Validator;
use File;

class ServiceVehicleController extends Controller
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
        $data['vehicle'] = Vehicle::all();
        $data['beban'] = Beban::all();
        $data['vendor'] = Vendor::all();
        $data['insurance'] = Insurance::all();
        $data['category'] = Category::all();
        $data['service_transaction'] = ServiceVehicle::all();
        return view('servicevehicle/index', $data); 
    }

    public function get_prop_vehicle($id)
    {
        $prop = Vehicle::find($id);
        return $prop;
    }

    public function servicevehiclelist(Request $request)
    {
        $columns = array(
            0 => 'id',
            1 => 'vehicle_id',
            2 => 'beban_id',
            3 => 'insurance_id',
            4 => 'vendor_id',
            5 => 'trans_no',
            6 => 'file',
            7 => 'request_by',
            8 => 'service_km',
            9 => 'next_service',
            10 => 'next_service_date',
            11 => 'nilai_total',
            12 => 'finance_id',
            13 => 'bukti_bayar',
            14 => 'tanggal_bayar',
            15 => 'desc',
            16 => 'status',
        );
        // return $totalData;
        $totalData = ServiceVehicle::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika ada get pada pencarian beban_id
        if (empty($request->input('search.value'))) {
            $servicevehicles = ServiceVehicle::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $servicevehicles = ServiceVehicle::where('beban_id','LIKE', "%{$search}%")
                                    ->orWhere('name','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = ServiceVehicle::where('beban_id','LIKE', "%{$search}%")
                                    ->orWhere('trans_no','LIKE',"%{$search}%")
                                    ->count();
        }

        // custom filter query here
        if (!empty($request->input('beban_id'))) {
            $servicevehicles = ServiceVehicle::where('beban_id',"$request->beban_id")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = ServiceVehicle::where('beban_id', "$request->beban_id")
                                    ->count();
        }

        // // jika ada get pada pencarian vendor
        // if (empty($request->input('search.value'))) {
        //     $servicevehicles = ServiceVehicle::offset($start)
        //                         ->limit($limit)
        //                         ->orderBy($order, $dir)
        //                         ->get();
        // } else {
        //     $search = $request->input('search.value');
        //     // definisikan parameter pencarian disini dengan kondisi orwhere
        //     $servicevehicles = ServiceVehicle::where('vendor_id','LIKE', "%{$search}%")
        //                             ->orWhere('name','LIKE',"%{$search}%")
        //                             ->offset($start)
        //                             ->limit($limit)
        //                             ->orderBy($order, $dir)
        //                             ->get();

        //     $totalFiltered = ServiceVehicle::where('vendor_id','LIKE', "%{$search}%")
        //                             ->orWhere('name','LIKE',"%{$search}%")
        //                             ->count();
        // }

        // // custom filter query here
        // if (!empty($request->input('vendor_id'))) {
        //     $servicevehicles = ServiceVehicle::where('vendor_id',"$request->vendor_id")
        //                             ->offset($start)
        //                             ->limit($limit)
        //                             ->orderBy($order, $dir)
        //                             ->get();

        //     $totalFiltered = ServiceVehicle::where('vendor_id', "$request->vendor_id")
        //                             ->count();
        // }

        // jika ada get pada pencarian 
        // if (empty($request->input('search.value'))) {
        //     $servicevehicles = ServiceVehicle::offset($start)
        //                         ->limit($limit)
        //                         ->orderBy($order, $dir)
        //                         ->get();
        // } else {
        //     $search = $request->input('search.value');
        //     // definisikan parameter pencarian disini dengan kondisi orwhere
        //     $servicevehicles = ServiceVehicle::where('vehicle_id','LIKE', "%{$search}%")
        //                             ->orWhere('name','LIKE',"%{$search}%")
        //                             ->offset($start)
        //                             ->limit($limit)
        //                             ->orderBy($order, $dir)
        //                             ->get();

        //     $totalFiltered = ServiceVehicle::where('vehicle_id','LIKE', "%{$search}%")
        //                             ->orWhere('name','LIKE',"%{$search}%")
        //                             ->count();
        // }

        // // custom filter query here
        // if (!empty($request->input('beban_id'))) {
        //     $servicevehicles = ServiceVehicle::where('vehicle_id',"$request->vehicle_id")
        //                             ->offset($start)
        //                             ->limit($limit)
        //                             ->orderBy($order, $dir)
        //                             ->get();

        //     $totalFiltered = ServiceVehicle::where('vehicle_id', "$request->vehicle_id")
        //                             ->count();
        // }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($servicevehicles)) {
            foreach ($servicevehicles as $ro) {
                $edit = route('editservicevehicle', $ro->id);
                $delete = route('deletedservicevehicle', $ro->id);
                $row['no'] = $no;
                $row['vehicle_id'] = $ro->vehicle->name;
                $row['beban_id'] = $ro->beban->nama;
                $row['insurance_id'] = $ro->insurance->name;
                $row['vendor_id'] = $ro->vendor->nama;
                $row['trans_no'] = "SV". $ro->requestbeban->kode_beban;
                $row['file'] = "<a href=".url('uploads/file_service_kendaraan/' .$ro->file).">"."Tagihan";
                $row['request_by'] = $ro->request_by;
                $row['service_km'] = $ro->service_km;
                $row['next_service'] = $ro->next_service;
                $row['next_service_date'] = $ro->next_service_date;
                $row['nilai_total'] ="Rp.".number_format($ro->nilai_total,2,',',',');
                // $row['nilai_total'] = $ro->nilai_total;
                // $row['nilai_total'] = "Rp." . numberformat($ro->nilai_total,2,',','.');
                $row['finance_id'] = $ro->finance->nama;
                if ($ro->status == "APPROVED") {
                    $row['status'] = "<span class='label label-success'> APPROVED</span>";
                } else if ($ro->status == "REJECTED") {
                    $row['status'] = "<span class='label label-danger'> REJECTED</span>";
                } else if ($ro->status == "CANCEL") {
                    $row['status'] = "<span class='label label-warning'> CANCEL</span>";
                } else if ($mro->status == "PAID"){
                    $row['status'] = "<span class='label label-primary'> PAID</span>";
                };
                $row['bukti_bayar'] = "<a href=".url('uploads/bukti_bayar_service_kendaraan/' .$ro->file).">"."Bukti Bayar";
                $row['tanggal_bayar'] = $ro->tanggal_bayar;
                $row['desc'] = $ro->desc;
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
}
 
    public function create()
    {
        //
        $data['emp'] = Employee::all();
        $data['vehicle'] = Vehicle::all();
        $data['beban'] = Beban::all();
        $data['insurance'] = Insurance::all();
        $data['vendor'] = Vendor::all();
        $data['category'] = Category::all();
        $data['service_transaction'] = ServiceVehicle::all();
        // running number
        $code = "/SRV/VEH/";
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
        return view('servicevehicle/create',$data);
        $servicevehicle->created_by = Auth::id();
        $servicevehicle->created_at = Carbon::now()->toDateTimeString();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $request->all();
        // $dekre = $request->dekre;
        // $cek_num = DB::table('service_transaction')->where('vehicle_id', $request->name)->where('beban_id', $request->nama)->whereNull('deleted_at')->orderBy('id','desc')->get();
        // $cek = DB::table('service_transaction')->where('vehicle_id', $request->name)->where('beban_id', $request->nama)->whereNull('deleted_at')->orderBy('id','desc')->first();
        // $request->validate([
            // 'vehicle_id'=>'required',
            // 'beban_id'=>'required',
            // 'insurance_id'=>'required',
            // 'vendor_id'=>'required',
            // 'trans_no'=>'required',
            // 'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            // 'request_by'=>'required',
            // 'service_km'=>'required|regex:/^[0-9]+$/',
            // 'next_service'=>'required|regex:/^[0-9]+$/',
            // 'next_service_date'=>'required',
            // 'nilai_total'=>'required|regex:/^[0-9]+$/',
            // 'finance_id'=>'required',
            // 'bukti_bayar'=>'required',
            // 'tanggal_bayar'=>'required',
            // 'desc'=>'required',
            // 'status'=>'required'
        // ]);

        // if($error){
        $servicevehicle = new ServiceVehicle();
        $servicevehicle->vehicle_id = $request->vehicle_id;
        $servicevehicle->beban_id = $request->beban_id;
        $servicevehicle->vendor_id = $request->vendor_id;
        $servicevehicle->insurance_id = $request->insurance_id;
        $servicevehicle->trans_no = $request->trans_no;
        if($request->hasFile('file')){
            $request->file('file')->move('uploads/file_service_kendaraan/',$request->file('file')->getClientOriginalName());
            $servicevehicle->file = $request->file('file')->getClientOriginalName();
        }
        $servicevehicle->request_by = $request->request_by;
        $servicevehicle->service_km = $request->service_km;
        $servicevehicle->next_service = $request->next_service;
        $servicevehicle->next_service_date = $request->next_service_date;
        $servicevehicle->finance_id = $request->finance_id;
        $servicevehicle->nilai_total = $request->nilai_total;
        if($request->hasFile('bukti_bayar')){
            $request->file('bukti_bayar')->move('uploads/bukti_bayar_service_kendaraan/',$request->file('bukti_bayar')->getClientOriginalName());
            $servicevehicle->bukti_bayar = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $servicevehicle->tanggal_bayar = $request->tanggal_bayar;
        $servicevehicle->desc = $request->desc;
        $servicevehicle->status = $request->status;
        $servicevehicle->created_by = Auth::id();
        $servicevehicle->created_at = Carbon::now()->toDateTimeString();
        $servicevehicle->save();
        $lastid = DB::table('service_transaction')->orderBy('id', 'desc')->first();
        $logid = $lastid->id;
        $save_log= New ServiceTransLog();
        $datalog = 
            [
                'transaction_id' => $logid,
                'status' => $request->status,
                'reason' => $request->keterangan,
                'created_at' => Carbon::now()->toDateTimeString(),
                'created_by' => Auth::id(),
            ];
            //save transactionlog
            $save = $save_log->create($datalog);
            //jika berhasil save
            if($save){
                $savetransactionitem = new TransactionItem();
                $dateitem = [];
                $dataitem = 
                [
                    
                    'transaction_id' => $logid,
                    'desc' => $request->desc_item,
                    'qty' => $request->qty_item,
                    'qty_satuan' =>$request->qty_satuan_item,
                    'harga_satuan' => $request->harga_satuan_item,
                    'harga_total' => $request->harga_total_item,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'created_by' => Auth::id(),
                ];
            }
            // return $dataitem;
        $save = $savetransactionitem->create($dataitem);
        return redirect('/servicevehicle')->with('success', 'Data berhasil ditambahkan');
        //kirim data ke log
    }

        /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceVehicle $data_request,$id)
    {
        //
        $data["dd"] = $data_request::find($id);
        $data['emp'] = Employee::all();
        $data['vehicle'] = Vehicle::all();
        $data['beban'] = Beban::all();
        $data['insurance'] = Insurance::all();
        $data['vendor'] = Vendor::all();
        $data['category'] = Category::all();
        $data['service_transaction'] = ServiceVehicle::all();
        return view('servicevehicle/edit')->with($data);
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
            'vehicle_id'=>'required',
            'beban_id'=>'required',
            'insurance_id'=>'required',
            'vendor_id'=>'required',
            // 'trans_no'=>'required',
            // 'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120',
            // 'request_by'=>'required',
            // 'service_km'=>'required|regex:/^[0-9]+$/',
            // 'next_service'=>'required|regex:/^[0-9]+$/',
            // 'next_service_date'=>'required',
            // 'nilai_total'=>'required|regex:/^[0-9]+$/',
            // 'finance_id'=>'required',
            // 'bukti_bayar'=>'required',
            // 'tanggal_bayar'=>'required',
            // 'desc'=>'required',
            'status'=>'required'
        ]);

        $servicevehicle = ServiceVehicle::find($id);
        $servicevehicle->vehicle_id = $request->vehicle_id;
        $servicevehicle->beban_id = $request->beban_id;
        $servicevehicle->vendor_id = $request->vendor_id;
        $servicevehicle->insurance_id = $request->insurance_id;
        $servicevehicle->trans_no = $request->trans_no;
        if($request->hasFile('file')){
            $request->file('file')->move('uploads/file_service_kendaraan/',$request->file('file')->getClientOriginalName());
            $servicevehicle->file = $request->file('file')->getClientOriginalName();
        }
        $servicevehicle->request_by = $request->request_by;
        $servicevehicle->service_km = $request->service_km;
        $servicevehicle->next_service = $request->next_service;
        $servicevehicle->next_service_date = $request->next_service_date;
        $servicevehicle->finance_id = $request->finance_id;
        $servicevehicle->nilai_total = $request->nilai_total;
        if($request->hasFile('bukti_bayar')){
            $request->file('bukti_bayar')->move('uploads/bukti_bayar_service_kendaraan/',$request->file('bukti_bayar')->getClientOriginalName());
            $servicevehicle->bukti_bayar = $request->file('bukti_bayar')->getClientOriginalName();
        }
        $servicevehicle->tanggal_bayar = $request->tanggal_bayar;
        $servicevehicle->status = $request->status;
        $servicevehicle->desc = $request->desc;
        $servicevehicle->updated_by = Auth::id();
        $servicevehicle->updated_at = Carbon::now()->toDateTimeString();
        $servicevehicle->save();
        $lastid = DB::table('service_transaction')->orderBy('id', 'desc')->first();
        $logid = $lastid->id;
        $save_log= New ServiceTransLog();
        $datalog = 
            [
                'transaction_id' => $logid,
                'status' => $request->status,
                'reason' => $request->keterangan,
                'updated_at' => Carbon::now()->toDateTimeString(),
                'updated_by' => Auth::id(),
            ];
            $save = $save_log->create($datalog);
        return redirect('/servicevehicle')->with('success', 'Data berhasil di Update');
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
        $trans= ServiceVehicle::find($id);
        $trans->deleted_by = Auth::id();
        $trans->deleted_at = Carbon::now()->toDateTimeString();
        if($trans->save()){
            return response()->json([
                'success' => 'Service Vehicle berhasil dihapus'
            ]);
        }
    }

    public function servicevehiclelistitem(Request $request)
    {
        $columns = array(
            0 => 'id',
            1 => 'transaction_item',
            2 => 'desc',
            3 => 'qty',
            4 => 'qty_satuan',
            5 => 'harga_satuan',
            6 => 'harga_total'
        );
        // return $totalData;
        $totalData = TransactionItem::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

//collection data here
$data = array();
$no = 1;
if (!empty($servicevehiclesitem)) {
    foreach ($servicevehiclesitem as $ro) {
        // $edit = route('editservicevehicle', $ro->id);
        // $delete = route('deletedservicevehicle', $ro->id);
        $row['no'] = $no;
        $row['transaction_id'] = $ro->requestbeban->nama;
        $row['desc'] = $ro->desc;
        $row['qty'] = $ro->qty;
        $row['qty_satuan'] = $ro->qty_satuan;
        $row['harga_satuan'] = $ro->harga_satuan;
        $row['harga_total'] = $ro->harga_total;
        // $row['action'] = " 
        // <a href=' $edit ' class='btn btn-warning btn-xs'><span class='glyphicon glyphicon-pencil'></span></a>
        // <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
        // ";
        $data[] = $row;
        $no++;
        
    }
    //return $data;
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
}
