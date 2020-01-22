<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Employee;
use App\PettyCash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PettyCashController extends Controller
{
    //

    public function transaction()
    {
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['trans_no'] = PettyCash::all();
        $data['payment_method'] = DB::table('master_payment_method')->get();
        $data['trans_type'] = DB::table('master_transaction_type')->get();
        $data['vehicle'] = DB::table('master_vehicle')->get();
        return view('petty_cash.transaction.index', $data);
    }

    public function store(Request $request)
    {
        $dekre = $request->dekre;
        $cek_num = DB::table('kas_kecil_transaction')->where('karyawan_id', $request->nik)->where('payment_method_id', $request->payment_method)->whereNull('deleted_at')->orderBy('id','desc')->get();
        $cek = DB::table('kas_kecil_transaction')->where('karyawan_id', $request->nik)->where('payment_method_id', $request->payment_method)->whereNull('deleted_at')->orderBy('id','desc')->first();
        $error = $request->validate([
            'tanggal' => 'required',
            'dekre' => 'required',
            'nik' => 'required',
            'payment_method' => 'required',
            'trans_desc' => 'required',
            'amount' => 'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120'
        ]);

        $kas = new PettyCash();
        if ($dekre == 'debit') {
            $kas->karyawan_id = $request->nik;
            $kas->vehicle_id = $request->vehicle_id;
            $kas->tanggal = $request->tanggal;
            $kas->transaction_type_id = $request->trans_type_store;
            $kas->transaction_desc = $request->trans_desc;
            $kas->payment_method_id = $request->payment_method;
            $kas->debit = $request->amount;
            $kas->trans_no = $request->trans_no;
            $kas->adjustment_ref = $request->adjustment_for;
            // jika saldo awal maka balance = amount
            if ($cek_num->count() == 0) {
                $kas->balance = $request->amount;
            }
            else {
                // jika ada saldo maka saldo akhir di tambah amount
                $kas->balance = $request->amount + $cek->balance;
            }
            if ($files = $request->file('file')) {
                $destinationPath = 'uploads/kas_kecil/'; // upload path
                $bukti = $request->nik. '_' .Carbon::now()->timestamp . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $bukti);
                $kas->file = $bukti;
            }
            $kas->created_by = Auth::id();
            $kas->created_at = Carbon::now()->toDateTimeString();
            $save = $kas->save();
            if ($save) {
                return response()->json([
                    'status' => true,
                    'message' => 'transaksi berhasil di proses'
                ], 200);
            }
        }
        else {
            // jika saldo cukup
            if ($request->amount <= $cek->balance) {
                $kas->karyawan_id = $request->nik;
                $kas->vehicle_id = $request->vehicle_id;
                $kas->tanggal = $request->tanggal;
                $kas->transaction_type_id = $request->trans_type;
                $kas->transaction_desc = $request->trans_desc;
                $kas->payment_method_id = $request->payment_method;
                $kas->credit = $request->amount;
                $kas->trans_no = $request->trans_no;
                $kas->balance = $cek->balance - $request->amount;
                $kas->adjustment_ref = $request->adjustment_for;
                if ($files = $request->file('file')) {
                    $destinationPath = 'uploads/kas_kecil/'; // upload path
                    $bukti = $request->nik. '_' .Carbon::now()->timestamp . "." . $files->getClientOriginalExtension();
                    $files->move($destinationPath, $bukti);
                    $kas->file = $bukti;
                }
                $kas->created_by = Auth::id();
                $kas->created_at = Carbon::now()->toDateTimeString();
                $save = $kas->save();
                if ($save) {
                    return response()->json([
                        'status' => true,
                        'message' => 'transaksi berhasil di proses'
                    ], 200);
                }
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'saldo anda tidak cukup'
                ], 200);
            }
            
        }
    }

    public function get_balance(Request $request)
    {
        $karyawan_id = $request->nik;
        $payment_method = $request->payment_method;
        $cek = DB::table('kas_kecil_transaction')->where('karyawan_id', $karyawan_id)->where('payment_method_id', $payment_method)->whereNull('deleted_at')->orderBy('id','desc')->first();
        $cek_num = DB::table('kas_kecil_transaction')->where('karyawan_id', $karyawan_id)->where('payment_method_id', $payment_method)->whereNull('deleted_at')->orderBy('id','desc')->get();
        $code_beban = Employee::find($request->nik)->bebanKasKecil->kode_beban;
        // tangakp error jika kode beban belum di set
        
        // running number
        $code = "/RES/STO/";
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
        $rn_a = DB::table('running_number')->where('code',$code_beban)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code_beban)->get(['rn','is_new_number']);
            $trans_no = sprintf('%04d', $rn[0]->rn+1).$code_beban.$bulan."/".$tahun;
        }
        // end of running number
        if ($cek_num->count() > 0) {
            return response()->json([
                'status' => true,
                'data' => $cek,
                'trans_no' => $trans_no
            ], 200);
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'saldo balance tidak ada',
                'trans_no' => $trans_no
            ], 200);
        }

    }

    // list json datatables
    public function kasKecilList(Request $request)
    {
        // form filter
        $nik = $request->input('filter_nik');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');
        $type = $request->input('filter_type');
        $payment = $request->input('filter_payment');
        $dekre = $request->input('filter_dekre');
        $vehicle = $request->input('filter_vehicle');

        // definisi orderable column
        $columns = array(
            0 => 'a.id',
            1 => 'b.nik',
            2 => 'b.nama',
            3 => 'a.tanggal',
            4 => 'c.name',
            5 => 'a.transaction_desc',
            6 => 'd.name',
            7 => 'a.debit',
            8 => 'a.credit',
            9 => 'a.balance',
            10 => 'a.file',
        );

        $totalData = PettyCash::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            // $dataAbsen = KaryawanAbsensi::offset($start)
            //                     ->limit($limit)
            //                     ->orderBy($order, $dir)
            //                     ->get();
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            or a.trans_no like '%$search%'
            and a.deleted_at is null
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            // array ketika terjadi pencarian maka akan count data yang di cari, karena menggunakan raw query maka data yang tampil berupa array
            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            or a.trans_no like '%$search%'
            and a.deleted_at is null
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($start_date) || !empty($end_date) || !empty($payment) || !empty($type) || !empty($dekre) || !empty($vehicle) ) {
            $search = $request->input('search.value');
            $nik = (!empty($nik)) ? "and a.karyawan_id = '$nik'" : '' ;
            if (!empty($start_date) && !empty($end_date) ) {
                $period = " and a.tanggal between '$start_date' and '$end_date' ";
            }
            else {
                $period = '';
            }
            $type = (!empty($type)) ? "and a.transaction_type_id = '$type'" : '' ;
            $payment = (!empty($payment)) ? "and a.payment_method_id = '$payment'" : '' ;
            if ($dekre == 'kredit') {
                $dekre = (!empty($dekre)) ? "and a.credit is not null" : '' ;
            }
            else {
                $dekre = (!empty($dekre)) ? "and a.debit is not null" : '' ;
            }
            $vehicle = (!empty($vehicle)) ? "and a.vehicle_id = '$vehicle'" : '' ;

            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, a.karyawan_id, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            $nik
            $period $type $payment $dekre $vehicle
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            $nik
            $period $type $payment $dekre $vehicle
            ")[0]->filtered;
           

        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataAbsen)) {
            foreach ($dataAbsen as $ro) {
                $edit = route('kaskecil.edit', $ro->id);
                $delete = $ro->id;

                $row['bulkDelete'] = "<input type='checkbox' name='deleteAll[]' onclick='partialSelected()' class='bulkDelete' id='bulkDeleteName' value='$ro->id'>";
                $row['no'] = $no;
                $row['nik'] = $ro->nik;
                $row['trans_no'] = $ro->trans_no;
                $row['nama'] = $ro->nama;
                $row['tanggal'] = $ro->tanggal;
                $row['type_trans'] = $ro->type_trans;
                $row['transaction_desc'] = $ro->transaction_desc;
                $row['payment_method'] = $ro->payment_method;
                // $row['debit'] = $ro->debit;
                // $row['credit'] = $ro->credit;
                // $row['balance'] = $ro->balance;
                $row['debit'] = "Rp " . number_format($ro->debit,0,',','.');
                $row['credit'] = "Rp " . number_format($ro->credit,0,',','.');
                $row['balance'] = "Rp " . number_format($ro->balance,0,',','.');
                $row['file'] = "<a href='". asset('uploads/kas_kecil/') ."/".$ro->file."' target='_blank'>". $ro->file ."</a> ";
                $row['options'] = "
                <button class='btn btn-xs btn-warning' onclick='edit($ro->id)'><span class='glyphicon glyphicon-pencil'></span></button>
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

    public function destroy(Request $request){
        $request->validate([
            'reason_delete' => 'required'
        ]);
        $ws = PettyCash::find($request->id_delete);
        $ws->deleted_by = Auth::id();
        $ws->deleted_desc = $request->reason_delete;
        $ws->deleted_at = Carbon::now()->toDateTimeString();
        $ws->save();
        return response()->json([
            'status' => true,
            'message' => 'data berhasil di Delete',
        ]);
    }

    public function edit($id)
    {
        $data = PettyCash::find($id);
        return $data;
    }

    public function update(Request $request, $id)
    {
        $dekre = $request->dekre;
        $cek_num = DB::table('kas_kecil_transaction')->where('karyawan_id', $request->nik)->where('payment_method_id', $request->payment_method)->whereNull('deleted_at')->orderBy('id','desc')->get();
        $cek = DB::table('kas_kecil_transaction')->where('karyawan_id', $request->nik)->where('payment_method_id', $request->payment_method)->whereNull('deleted_at')->orderBy('id','desc')->first();
        $error = $request->validate([
            'tanggal' => 'required',
            'dekre' => 'required',
            'nik' => 'required',
            'payment_method' => 'required',
            'trans_desc' => 'required',
            'amount' => 'required',
            'update_desc' => 'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG, PDF, pdf|max:5120'
        ]);

        $kas = PettyCash::find($id);
        if ($dekre == 'debit') {
            $kas->karyawan_id = $request->nik;
            $kas->vehicle_id = $request->vehicle_id;
            $kas->tanggal = $request->tanggal;
            $kas->transaction_type_id = $request->trans_type_store;
            $kas->transaction_desc = $request->trans_desc;
            $kas->payment_method_id = $request->payment_method;
            $kas->debit = $request->amount;
            $kas->updated_desc = $request->update_desc;
            $kas->trans_no = $request->trans_no;
            $kas->adjustment_ref = $request->adjustment_for;
            // jika saldo awal maka balance = amount
            if ($cek_num->count() == 0) {
                $kas->balance = $request->amount;
            }
            else {
                // jika ada saldo maka saldo akhir di tambah amount
                $kas->balance = $request->amount + $cek->balance;
            }
            if ($files = $request->file('file')) {
                Storage::delete('uploads/kas_kecil/'.$kas->file);
                $destinationPath = 'uploads/kas_kecil/'; // upload path
                $bukti = $request->nik. '_' .Carbon::now()->timestamp . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $bukti);
                $kas->file = $bukti;
            }
            $kas->created_by = Auth::id();
            $kas->created_at = Carbon::now()->toDateTimeString();
            $save = $kas->save();
            if ($save) {
                return response()->json([
                    'status' => true,
                    'message' => 'transaksi berhasil di Update'
                ], 200);
            }
        }
        else {
            // jika saldo cukup
            if ($request->amount <= $cek->balance) {
                $kas->karyawan_id = $request->nik;
                $kas->vehicle_id = $request->vehicle_id;
                $kas->tanggal = $request->tanggal;
                $kas->transaction_type_id = $request->trans_type;
                $kas->transaction_desc = $request->trans_desc;
                $kas->payment_method_id = $request->payment_method;
                $kas->credit = $request->amount;
                $kas->balance = $cek->balance - $request->amount;
                $kas->updated_desc = $request->update_desc;
                $kas->trans_no = $request->trans_no;
                $kas->adjustment_ref = $request->adjustment_for;
                if ($files = $request->file('file')) {
                    Storage::delete('uploads/kas_kecil/'.$kas->file);
                    $destinationPath = 'uploads/kas_kecil/'; // upload path
                    $bukti = $request->nik. '_' .Carbon::now()->timestamp . "." . $files->getClientOriginalExtension();
                    $files->move($destinationPath, $bukti);
                    $kas->file = $bukti;
                }
                $kas->created_by = Auth::id();
                $kas->created_at = Carbon::now()->toDateTimeString();
                $save = $kas->save();
                if ($save) {
                    return response()->json([
                        'status' => true,
                        'message' => 'transaksi berhasil di proses'
                    ], 200);
                }
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'saldo anda tidak cukup'
                ], 200);
            }
            
        }
    }




}
