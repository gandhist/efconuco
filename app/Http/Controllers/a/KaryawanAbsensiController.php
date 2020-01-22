<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Employee;
use \App\KaryawanAbsensi;
use \App\WorkingSchedule;

use \App\Level;
use \App\Kantor;
use \App\Divisi;
use \App\Jabatan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KaryawanAbsensiController extends Controller
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

        // $totalData = DB::select('select COUNT(*) as filtered FROM karyawan_absensi a
        // LEFT JOIN karyawan b
        // ON a.nik = b.nik')[0]->filtered;
        // dd($totalData);

        // LOV provinsi dlsb di definisikan di sini dan di push ke array yang di kirim ke view
        return view('karyawan.absensi.index', $data);
    }

    public function absenListOne()
    {
        // mendapatkan absensi karyawan berdasarkan nik
        // $data = Employee::all();
        // foreach ($data as $key) {
        //     echo $key->nik;
        //     echo "<br>";
        //     echo $key->nama;
        //     echo "<br>";
        //     foreach ($key->absensi as $abs) {
        //         echo $abs->tanggal;
        //     } 
        //     echo "<hr>";
        // }

        // mendapatkan nama karyawan ketika di model karyawanabsensi
        // $data_absen = KaryawanAbsensi::all();
        // foreach ($data_absen as $key) {
        //     echo $key->karyawan['nama'];
        //     echo $key->karyawan_id;
        //     echo "<br>";
        //     echo $key->tanggal;
        //     echo "<br>";
        //     echo "<hr>";

        // }

        // working_schedule VS working_type
        $ws = WorkingSchedule::all();
        foreach ($ws as $key) {
            echo $key->karyawan_id;
            echo "<br>";
            echo $key->date;
            echo "<br>";
            echo $key->working_type['nama'];
            echo "<br>";
            //echo $key->working_type->working_hour;
            foreach ($key->working_type->working_hour as $schedule) {
                echo $schedule->start_time;
                echo $schedule->end_time;
            }
            echo "<hr>";
        }

        // working type vs working hour
        // $ws = \App\WorkingType::all();
        // foreach ($ws as $key) {
        //     echo $key->id;
        //     echo "<br>";
        //     echo $key->nama;
        //     echo "<br>";
        //     foreach ($key->working_hour as $ke ) {
        //         echo $ke->start_time;
        //         echo $ke->end_time;
        //     }
        //     echo "<hr>";
        // }
    }

    // list json datatables
    public function absensiList(Request $request)
    {
        // form filter
        $nik = $request->input('filter_nik');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');

        // definisi orderable column
        $columns = array(
            0 => 'a.id',
            1 => 'a.karyawan_id',
            2 => 'b.nama',
            3 => 'a.tanggal',
            4 => 'a.masuk',
            5 => 'a.pulang',
            6 => 'a.schedule_start',
            7 => 'a.schedule_end',
            8 => 'a.status',
            9 => 'a.keterangan',
        );

        $totalData = KaryawanAbsensi::count();
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
            SELECT a.id, b.nama, a.karyawan_id, a.tanggal, a.masuk, a.pulang, a.schedule_start, a.schedule_end, a.status, a.keterangan FROM karyawan_absensi a
            LEFT JOIN karyawan b
            ON a.karyawan_id = b.id
            where a.deleted_at is null
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            // di disable karena 
            // $dataAbsen = KaryawanAbsensi::where('nik','LIKE', "%{$search}%")
            //                         ->orWhere('tanggal','LIKE',"%{$search}%")
            //                         ->offset($start)
            //                         ->limit($limit)
            //                         ->orderBy($order, $dir)
            //                         ->get();
            $dataAbsen = DB::select("
            SELECT a.id, b.nama, a.karyawan_id, a.tanggal, a.masuk, a.pulang, a.schedule_start, a.schedule_end, a.status, a.keterangan FROM karyawan_absensi a
            LEFT JOIN karyawan b
            ON a.karyawan_id = b.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            and a.deleted_at is null
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            // $totalFiltered = KaryawanAbsensi::where('nik','LIKE', "%{$search}%")
            //                             ->orWhere('tanggal','LIKE',"%{$search}%")
            //                             ->count();

            // array ketika terjadi pencarian maka akan count data yang di cari, karena menggunakan raw query maka data yang tampil berupa array
            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM karyawan_absensi a
            LEFT JOIN karyawan b
            ON a.karyawan_id = b.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            and a.deleted_at is null
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($start_date) || !empty($end_date)) {

            if ($nik) {
                $dataAbsen = DB::table('karyawan_absensi as a')
                    ->select('a.id', 'b.nama', 'a.karyawan_id', 'a.tanggal', 'a.masuk', 'a.pulang', 'a.schedule_start', 'a.schedule_end', 'a.status', 'a.keterangan')
                    ->leftJoin('karyawan as b', 'a.karyawan_id', '=', 'b.id')
                    ->where('a.karyawan_id', $nik)
                    ->whereNull('a.deleted_at')
                    ->get();
                $totalFiltered = DB::table('karyawan_absensi as a')
                    ->leftJoin('karyawan as b', 'a.karyawan_id', '=', 'b.id')
                    ->where('a.karyawan_id', $nik)
                    ->count();
            }
            if ($start_date && $end_date) {
                $dataAbsen = DB::table('karyawan_absensi as a')
                    ->select('a.id', 'b.nama', 'a.karyawan_id', 'a.tanggal', 'a.masuk', 'a.pulang', 'a.schedule_start', 'a.schedule_end', 'a.status', 'a.keterangan')
                    ->leftJoin('karyawan as b', 'a.karyawan_id', '=', 'b.id')
                    ->whereBetween('a.tanggal', [$start_date, $end_date])
                    ->whereNull('a.deleted_at')
                    ->get();
                $totalFiltered = DB::table('karyawan_absensi as a')
                    ->leftJoin('karyawan as b', 'a.karyawan_id', '=', 'b.id')
                    ->whereBetween('a.tanggal', [$start_date, $end_date])
                    ->count();
            }
            if ($nik && $start_date && $end_date) {
                $dataAbsen = DB::table('karyawan_absensi as a')
                    ->select('a.id', 'b.nama', 'a.karyawan_id', 'a.tanggal', 'a.masuk', 'a.pulang', 'a.schedule_start', 'a.schedule_end', 'a.status', 'a.keterangan')
                    ->leftJoin('karyawan as b', 'a.karyawan_id', '=', 'b.id')
                    ->where('a.karyawan_id', $nik)
                    ->whereBetween('a.tanggal', [$start_date, $end_date])
                    ->whereNull('a.deleted_at')
                    ->get();
                $totalFiltered = DB::table('karyawan_absensi as a')
                    ->leftJoin('karyawan as b', 'a.karyawan_id', '=', 'b.id')
                    ->where('a.karyawan_id', $nik)
                    ->whereBetween('a.tanggal', [$start_date, $end_date])
                    ->whereNull('a.deleted_at')
                    ->count();
            }

            // $dataAbsen = DB::select("
            // SELECT a.id, b.nama, a.nik, a.tanggal, a.masuk, a.pulang, a.schedule_start, a.schedule_end, a.status, a.keterangan FROM karyawan_absensi a
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
                $edit = route('kehadiran.edit', $ro->id);
                $delete = route('kehadiran.destroy', $ro->id);

                $row['bulkDelete'] = "<input type='checkbox' name='deleteAll[]' onclick='partialSelected()' class='bulkDelete' id='bulkDeleteName' value='$ro->id'>";
                $row['no'] = $no;
                $row['nik'] = $ro->karyawan_id;
                $row['nama'] = $ro->nama;
                $row['tanggal'] = $ro->tanggal;
                $row['masuk'] = $ro->masuk;
                $row['pulang'] = $ro->pulang;
                $row['schedule_start'] = $ro->schedule_start;
                $row['schedule_end'] = $ro->schedule_end;
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
            'schedule_start' => 'required|regex:/^[0-9:]+$/',
            'schedule_end' => 'required|regex:/^[0-9:]+$/',
            'status' => 'required',
            'masuk' => 'required|regex:/^[0-9- :]+$/',
            'pulang' => 'required|regex:/^[0-9- :]+$/',
        ]);
        if ($error) {
            $absensi = new KaryawanAbsensi;
            $absensi->karyawan_id = $request->nik;
            $absensi->tanggal = $request->tanggal;
            $absensi->masuk = $request->masuk;
            $absensi->pulang = $request->pulang;

            $absensi->schedule_start = $request->schedule_start;
            $absensi->schedule_end = $request->schedule_end;
            $absensi->status = $request->status;
            $absensi->keterangan = $request->keterangan;
            $absensi->late_tolerance = $request->late_tolerance;
            $absensi->fee_lembur = $request->fee;
            $absensi->beban_id = $request->beban;
            $absensi->created_by = Auth::id();

            //save to db 
            $save = $absensi->save();
            if ($save) {
                // jika berhasil save
                return response()->json([
                    'status' => true,
                    'message' => 'Data Absensi berhasil di simpan'
                ], 200);
            } else {
                // jika berhasil save
                return response()->json([
                    'status' => false,
                    'message' => 'Data Absensi gagal di simpan'
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
        $data = KaryawanAbsensi::find($id);
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
            'schedule_start' => 'required|regex:/^[0-9:]+$/',
            'schedule_end' => 'required|regex:/^[0-9:]+$/',
            'status' => 'required',
            'masuk' => 'required|regex:/^[0-9- :]+$/',
            'pulang' => 'required|regex:/^[0-9- :]+$/',
        ]);

        // jika tidak ada error 
        if ($error) {
            // buat instance baru dari objek
            $data = KaryawanAbsensi::find($id);
            $data->karyawan_id = $request->nik;
            $data->tanggal = $request->tanggal;
            $data->masuk = $request->masuk;
            $data->pulang = $request->pulang;

            $data->schedule_start = $request->schedule_start;
            $data->schedule_end = $request->schedule_end;
            $data->status = $request->status;
            $data->keterangan = $request->keterangan;
            $data->late_tolerance = $request->late_tolerance;
            $data->fee_lembur = $request->fee;
            $data->beban_id = $request->beban;
            $data->updated_by = Auth::id();
            $save = $data->save();
            if ($save) {
                return response()->json([
                    'status' => true,
                    'message' => 'data berhasil di update'
                ], 201);
            } else {
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
        $data = KaryawanAbsensi::find($id);
        $data->deleted_by = Auth::id();
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
        $data = DB::table('karyawan_absensi')->whereIn('id', $request->id)->update($user_data);
        if ($data) {
            return response()->json([
                'status' => true,
                'message' => 'Data Terpilih berhasil di hapus'
            ]);
        }
    }

    // get data working schedule
    public function get_working_schedule(Request $request)
    {
        $data['lembur'] = WorkingSchedule::where('karyawan_id', $request->nik)->where('date', $request->tanggal)->first()->working_type->lembur;
        $data['karyawan'] = WorkingSchedule::where('karyawan_id', $request->nik)->where('date', $request->tanggal)->first()->karyawan->beban_id;
        $data['working_type'] = WorkingSchedule::where('karyawan_id', $request->nik)->where('date', $request->tanggal)->first()->working_type->working_hour;

        // $start_time = Carbon::parse($request->masuk);
        // $tolerance = explode(':',$data['working_type'][0]->late_tolerance);
        // $scheduled_start = Carbon::parse($data['working_type'][0]->start_time)->addMinutes($tolerance[1]);
        // if ($start_time > $scheduled_start) {
        //     $data['late'] = gmdate('H:i:s', $start_time->diffInSeconds($scheduled_start));
        // }
        // else {
        //     $data['late'] = 'good boy is not coming late, Fee : '.$data['lembur'].' hour : '.$data['working_type'][0]->late_tolerance;
        // }
        return $data;
    }
}
