<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Employee;
use \App\KaryawanLembur;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KaryawanLemburController extends Controller
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
        $data['dd'] = Employee::all();

        return view('karyawan.lembur.index', $data); 
    }

    // ajax request list datatablse
    public function overtimeList(Request $request){
         // form filter
         $nik = $request->input('filter_nik');
         $start_date = $request->input('filter_start_date');
         $end_date = $request->input('filter_end_date');
 
         // definisi orderable column
         $columns = array(
             0 => 'a.id',
             1 => 'a.karyawan_id',
             2 => 'nama',
             3 => 'a.tanggal',
             4 => 'a.mulai',
             5 => 'a.selesai',
             6 => 'a.status',
             7 => 'a.keterangan'
         );
 
         $totalData = KaryawanLembur::count();
         $totalFiltered = $totalData;
 
         $limit = $request->length;
         $start = $request->start;
         $order = $columns[$request->input('order.0.column')];
         $dir = $request->input('order.0.dir');
 
         // jika tidak ada get pada pencarian 
         if (empty($request->input('search.value'))) {
             // $dataAbsen = KaryawanLembur::offset($start)
             //                     ->limit($limit)
             //                     ->orderBy($order, $dir)
             //                     ->get();
             $dataAbsen = DB::select("
             SELECT a.id, b.nama , a.karyawan_id, a.tanggal, a.mulai, a.selesai, a.status, a.keterangan FROM karyawan_lembur a
             LEFT JOIN karyawan b
             ON a.karyawan_id = b.id
             where a.deleted_at is null
             ORDER BY $order $dir
             LIMIT $limit OFFSET $start
             ");
         } else {
             $search = $request->input('search.value');
             // definisikan parameter pencarian disini dengan kondisi orwhere
             // di disable karena 
             // $dataAbsen = KaryawanLembur::where('nik','LIKE', "%{$search}%")
             //                         ->orWhere('tanggal','LIKE',"%{$search}%")
             //                         ->offset($start)
             //                         ->limit($limit)
             //                         ->orderBy($order, $dir)
             //                         ->get();
             $dataAbsen = DB::select("
             SELECT a.id, b.nama, a.karyawan_id, a.tanggal, a.mulai, a.selesai, a.status, a.keterangan FROM karyawan_lembur a
             LEFT JOIN karyawan b
             ON a.karyawan_id = b.id
             where a.karyawan_id like '%$search%'
             or 
             b.nama like '%$search%'
             and a.deleted_at is null
             ORDER BY $order $dir
             LIMIT $limit OFFSET $start
             ", ['limit' => $limit, 'offset'=> $start, 'order'=>$order, 'dir'=> $dir] );
 
             // $totalFiltered = KaryawanLembur::where('nik','LIKE', "%{$search}%")
             //                             ->orWhere('tanggal','LIKE',"%{$search}%")
             //                             ->count();
 
             // array ketika terjadi pencarian maka akan count data yang di cari, karena menggunakan raw query maka data yang tampil berupa array
             $totalFiltered = DB::select("
             SELECT COUNT(b.nama) as filtered FROM karyawan_lembur a
             LEFT JOIN karyawan b
             ON a.karyawan_id = b.id
             where a.karyawan_id like '%$search%'
             or 
             b.nama like '%$search%'
             and a.deleted_at is null
             " )[0]->filtered;
         }
 
         // custom filter query here
         if (!empty($nik) || !empty($start_date) || !empty($end_date) ) {
 
             if ($nik) {
                 $dataAbsen = DB::table('karyawan_lembur as a')
                                ->select('a.id', 'b.nama', 'a.karyawan_id', 'a.tanggal', 'a.mulai', 'a.selesai', 'a.status', 'a.keterangan')
                                 ->leftJoin('karyawan as b', 'a.karyawan_id','=','b.id')
                                 ->where('a.karyawan_id',$nik)
                                 ->whereNull('a.deleted_at')
                                 ->get();
                 $totalFiltered = DB::table('karyawan_lembur as a')
                 ->leftJoin('karyawan as b', 'a.karyawan_id','=','b.id')
                 ->where('a.karyawan_id',$nik)
                 ->whereNull('a.deleted_at')
                 ->count();
             }
             if ($start_date && $end_date) {
                 $dataAbsen = DB::table('karyawan_lembur as a')
                                ->select('a.id', 'b.nama', 'a.karyawan_id', 'a.tanggal', 'a.mulai', 'a.selesai', 'a.status', 'a.keterangan')
                                 ->leftJoin('karyawan as b', 'a.karyawan_id','=','b.id')
                                 ->whereBetween('a.tanggal',[$start_date, $end_date])
                                 ->whereNull('a.deleted_at')
                                 ->get();
                 $totalFiltered = DB::table('karyawan_lembur as a')
                 ->leftJoin('karyawan as b', 'a.karyawan_id','=','b.id')
                 ->whereBetween('a.tanggal',[$start_date, $end_date])
                 ->whereNull('a.deleted_at')
                 ->count();
             }
             if ($nik && $start_date && $end_date) {
                 $dataAbsen = DB::table('karyawan_lembur as a')
                                ->select('a.id', 'b.nama', 'a.karyawan_id', 'a.tanggal', 'a.mulai', 'a.selesai', 'a.status', 'a.keterangan')
                                 ->leftJoin('karyawan as b', 'a.karyawan_id','=','b.id')
                                 ->where('a.karyawan_id',$nik)
                                 ->whereNull('a.deleted_at')
                                 ->whereBetween('a.tanggal',[$start_date, $end_date])
                                 ->get();    
                 $totalFiltered = DB::table('karyawan_lembur as a')
                 ->leftJoin('karyawan as b', 'a.karyawan_id','=','b.id')
                 ->where('a.karyawan_id',$nik)
                 ->whereNull('a.deleted_at')
                 ->whereBetween('a.tanggal',[$start_date, $end_date])
                 ->count();
             }
             
             // $dataAbsen = DB::select("
             // SELECT a.id, b.nama, a.nik, a.tanggal, a.mulai, a.selesai, a.schedule_start, a.schedule_end, a.status, a.keterangan FROM karyawan_lembur a
             // LEFT JOIN karyawan b
             // ON a.nik = b.nik
             // where a.nik = '$nik'
             // ");
         }
 
         //collection data here
         $data = array();
         $no = 1;
         if (!empty($dataAbsen)) {
             foreach ($dataAbsen as $ro) {
                 $edit = route('overtime.edit', $ro->id);
                 $delete = route('overtime.destroy', $ro->id);
 
                 $row['bulkDelete'] = "<input type='checkbox' name='deleteAll[]' onclick='partialSelected()' class='bulkDelete' id='bulkDeleteName' value='$ro->id'>";
                 $row['no'] = $no;
                 $row['nik'] = $ro->karyawan_id;
                 $row['nama'] = $ro->nama;
                 $row['tanggal'] = $ro->tanggal;
                 $row['mulai'] = $ro->mulai;
                 $row['selesai'] = $ro->selesai;
                 $row['status'] = $ro->status == 1 ? "<span class='label label-success'> Active</span>" : "<span class='label label-danger'> Inactive </span> ";
                 $row['keterangan'] = $ro->keterangan;
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $error = $request->validate([
            'nik' => 'required',
            'tanggal' => 'required',
            'mulai' => 'required|regex:/^[0-9- :]+$/',
            'selesai' => 'required|regex:/^[0-9- :]+$/',
            'status' => 'required',
        ]);
        if ($error) {
            $data = new KaryawanLembur;
            $data->karyawan_id = $request->nik;
            $data->tanggal = $request->tanggal;
            $data->mulai = $request->mulai;
            $data->selesai = $request->selesai;

            $data->status = $request->status;
            $data->keterangan = $request->keterangan;
            $data->created_by = Auth::id();

            //save to db 
            $save = $data->save();
            if ($save) {
                // jika berhasil save
                return response()->json([
                    'status' => true,
                    'message' => 'Data Lembur berhasil di simpan'
                ], 200);
            }
            else {
                // jika berhasil save
                return response()->json([
                    'status' => false,
                    'message' => 'Data Lembur gagal di simpan'
                ], 401);
            }

        }
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
        $data = KaryawanLembur::find($id);
        return $data;
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
            'nik' => 'required',
            'tanggal' => 'required',
            'mulai' => 'required|regex:/^[0-9- :]+$/',
            'selesai' => 'required|regex:/^[0-9- :]+$/',
            'status' => 'required',
        ]);

        // jika tidak ada error 
        if ($error) {
            // buat instance baru dari objek
            $data = KaryawanLembur::find($id);
            $data->karyawan_id = $request->nik;
            $data->tanggal = $request->tanggal;
            $data->mulai = $request->mulai;
            $data->selesai = $request->selesai;

            $data->status = $request->status;
            $data->keterangan = $request->keterangan;
            $data->updated_by = Auth::id();
            $save = $data->save();
            if ($save) {
                return response()->json([
                    'status' => true,
                    'message' => 'data berhasil di update'
                ], 201);
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'data gagal di update'
                ], 401);
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
        //
        $data = KaryawanLembur::find($id);
        $data->deleted_at = Auth::id();
        $data->deleted_at = Carbon::now()->toDateTimeString();
        $delete = $data->save();
        if ($delete) {
            return response()->json([
                'status' => true,
                'message' => 'data berhasil di Delete'
            ], 201);
        }
    }

        // mass delete
        public function mass_delete(Request $request)
        {
            $user_data = [
                'deleted_by' => Auth::id(),
                'deleted_at' => Carbon::now()->toDateTimeString()
            ];
            $data = DB::table('karyawan_lembur')->whereIn('id', $request->id)->update($user_data);
            if ($data) {
                return response()->json([
                    'status' => true,
                    'message' => 'Data Terpilih berhasil di hapus'
                ]);
            }
        }
}
