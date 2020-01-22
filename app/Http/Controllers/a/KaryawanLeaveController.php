<?php

namespace App\Http\Controllers;

use App\Employee;
use App\LeaveType;
use App\KaryawanLeave;
use App\KaryawanLeaveTrail;
use App\KaryawanLeaveLog;
use App\KaryawanLeaveQuota;
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

class KaryawanLeaveController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }
    /**
     * Validate form and submit
     *
     * @return void
     */
    public function send()
    {
        $validator = Validator::make(request()->all(), [
            'nik'  => 'required|max:50',
            'date_start'  => 'required',
            'date_end'  => 'required',
            'leave_quota_id'  => 'required',
            'leave_type_id' => 'required|max:100|leave_type_id',
        ]);

        if ($validator->fails()) {
            redirect()
                ->back()
                ->withErrors($validator->errors());
        }
        //Mengecek data double
        $cek = DB::table('karyawan_leave_trail')->where('karyawan_id', $karyawan_id)->where('leave_type_id', $request->leave_type_id)->whereBetween('leave_date', [$request->date_start, $request->date_end])->exists();
        if ($cek) {
            $tanggal = DB::table('karyawan_leave_trail')->where('karyawan_id', $karyawan_id)->where('leave_type_id', $request->leave_type_id)->whereBetween('leave_date', [$request->date_start, $request->date_end])->get('leave_date');
            return response()->json([
                'status' => false,
                'message' => 'cuti sudah diambil',
                'data' => $tanggal
            ], 404);
        }
        $leave_quota = KaryawanLeaveQuota::where('is_taken', '=', '0')->where('karyawan_id')->where('leave_type_id')->count();
        return $leave_quota;
    }


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
        $data['leave_type'] = LeaveType::all();
        $data['karyawan_leave'] = KaryawanLeave::orderBy('id', 'desc')->get();
        return view('karyawanleave/index', $data);
        // $karyawan_leave = DB::table('karyawan_leave')
        // ->orderBy('id','desc')
        // ->get();
    }

    // public function karlevlist(Request $request)
    // {
    //     $nik = $request->input('filter_nik');
    //     $start_date = $request->input('filter_start_date');
    //     $end_date = $request->input('filter_end_date');
    //     $master_leave_type = $request->input('filter_wt');

    //     $columns = array(
    //         0 => 'a.id',
    //         1 => 'c.nik',
    //         2 => 'c.nama',
    //         3 => 'a.date',
    //         4 => 'b.nama',
    //         5 => 'b.start_time',
    //         6 => 'b.end_time'
    //     );

    //     $totalData = KaryawanLeave::count();
    //     $totalFiltered = $totalData;

    //     $limit = $request->length;
    //     $start = $request->start;
    //     $order = $columns[$request->input('order.0.column')];
    //     $dir = $request->input('order.0.dir');

    //     if (empty($request->input('search.value'))) {
    //         $dataKarlev = DB::select("
    //             SELECT a.id, a.nik, c.nik, c.nama, a.date, a.leave_type_id, a.mass_id, b.nama AS master_leave_type , b.day, b.start_time, b.end_time FROM working_schedule a 
    //             LEFT JOIN 
    //             (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM master_leave_type a LEFT JOIN working_hour b
    //             ON a.id = b.leave_type_id ) b 
    //             ON a.leave_type_id = b.id
    //             INNER JOIN karyawan c 
    //             ON a.nik = c.id
    //             where a.deleted_at is null
    //             order by $order $dir
    //             limit $limit offset $start
    //         ");
    //     }
    //     else {
    //         $search = $request->input('search.value');
    //             $dataKarlev = DB::select("
    //                 SELECT a.id, a.nik, c.nik, c.nama, a.date, a.leave_type_id, a.mass_id, b.nama AS master_leave_type , b.day, b.start_time, b.end_time FROM working_schedule a 
    //                 LEFT JOIN 
    //                 (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM master_leave_type a LEFT JOIN working_hour b
    //                 ON a.id = b.leave_type_id ) b 
    //                 ON a.leave_type_id = b.id
    //                 INNER JOIN karyawan c 
    //                 ON a.nik = c.id
    //                 where a.deleted_at is null
    //                 and c.nama like '%$search%'
    //                 or c.nik like '%$search%'
    //                 or b.nama like '%$search%'
    //                 ORDER BY $order  $dir
    //                 LIMIT $limit OFFSET $start
    //             ");
    //         $totalFiltered = DB::select("
    //             SELECT count(a.id) as filtered FROM working_schedule a 
    //             LEFT JOIN 
    //             (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM master_leave_type a LEFT JOIN working_hour b
    //             ON a.id = b.leave_type_id ) b 
    //             ON a.leave_type_id = b.id
    //             INNER JOIN karyawan c 
    //             ON a.nik = c.id
    //             where a.deleted_at is null
    //             and c.nama like '%$search%'
    //             or c.nik like '%$search%'
    //             or b.nama like '%$search%'
    //             order by $order $dir
    //             limit $limit offset $start
    //         ")[0]->filtered;
    //     }

    //     // custom filter query here
    //     if (!empty($nik) || !empty($start_date) || !empty($end_date) || !empty($master_leave_type) ) {
    //         $nik = (!empty($nik)) ? "and a.nik = '$nik'" : '' ;
    //         $master_leave_type = (!empty($master_leave_type)) ? "and a.leave_type_id = '$master_leave_type'" : '' ;
    //         if (!empty($start_date) && !empty($end_date) ) {
    //             $period = " and a.date between '$start_date' and '$end_date' ";
    //         }
    //         else {
    //             $period = '';
    //         }

    //         $dataKarlev = DB::select("
    //         SELECT a.id, a.nik, c.nik, c.nama, a.date, a.leave_type_id, a.mass_id, b.nama AS master_leave_type , b.day, b.start_time, b.end_time FROM working_schedule a 
    //         LEFT JOIN 
    //         (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM master_leave_type a LEFT JOIN working_hour b
    //         ON a.id = b.leave_type_id ) b 
    //         ON a.leave_type_id = b.id
    //         INNER JOIN karyawan c 
    //         ON a.nik = c.id
    //         where a.deleted_at is null
    //         $nik
    //         $master_leave_type
    //         $period
    //         ORDER BY $order  $dir
    //         LIMIT $limit OFFSET $start
    //     ");
    //     $totalFiltered = DB::select("
    //         SELECT count(a.id) as filtered FROM working_schedule a 
    //         LEFT JOIN 
    //         (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM master_leave_type a LEFT JOIN working_hour b
    //         ON a.id = b.leave_type_id ) b 
    //         ON a.leave_type_id = b.id
    //         INNER JOIN karyawan c 
    //         ON a.nik = c.id
    //         where a.deleted_at is null
    //         $nik
    //         $master_leave_type
    //         $period
    //         ")[0]->filtered;

    //     }

    //     //collection data here
    //     $data = array();
    //     $no = 1;
    //     if (!empty($dataKarlev)) {
    //         foreach ($dataKarlev as $ro) {
    //             $edit = route('karlev.edit', $ro->mass_id);
    //             $delete = route('karlev.destroy', $ro->id);

    //             $row['no'] = $no;
    //             $row['nik'] = $ro->nik;
    //             $row['nama'] = $ro->nama;
    //             $row['tanggal'] = $ro->date;
    //             $row['master_leave_type'] = $ro->master_leave_type;
    //             $row['start_time'] = $ro->start_time;
    //             $row['end_time'] = $ro->end_time;
    //             $row['options'] = "
    //             <button class='btn btn-xs btn-warning' onclick='edit($ro->mass_id)'><span class='glyphicon glyphicon-pencil'></span></button>
    //             <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
    //             ";
    //             $data[] = $row;
    //             $no++;
    //         }
    //     }

    //     // return data json
    //     $jsonData = array(
    //         'draw' => intval($request->input('draw')),
    //         'recordsTotal' => intval($totalData),
    //         'recordsFiltered' => intval($totalFiltered),
    //         'data' => $data,
    //     );

    //     echo json_encode($jsonData);


    //     // $ws = KaryawanLeave::all();
    //     // foreach ($ws as $key) {
    //     //     echo $key->nik;
    //     //     echo $key->karyawan['nama'];
    //     //     echo "<br>";
    //     //     echo $key->date;
    //     //     echo "<br>";
    //     //     echo $key->master_leave_type['nama'];
    //     //     echo "<br>";
    //     //     //echo $key->master_leave_type->working_hour;
    //     //     foreach ($key->master_leave_type->working_hour as $schedule) {
    //     //         echo $schedule->start_time;
    //     //         echo $schedule->end_time;
    //     //     }
    //     //     echo "<hr>";
    //     // }
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['karyawan'] = Employee::all();
        $data['leave_type'] = LeaveType::all();
        $data['karyawanleave'] = KaryawanLeave::all();
        return view('karyawanleave/create', $data);
        $karyawan_leave->created_by = Auth::id();
        $karyawan_leave->created_at = Carbon::now()->toDateTimeString();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $start_date = Carbon::parse($request->input('date_start'));
        $end_date = Carbon::parse($request->input('date_end'));
        $period = CarbonPeriod::create($start_date, $end_date);
        $interval = $end_date->diffInDays($start_date);

        $error = $request->validate([
            'nik' => 'required',
            'keterangan' => 'required',
            'date_start' => 'required',
            'date_end' => 'required',
            'leave_type_id' => 'required',
            'file' => 'mimes:jpeg,png,jpg,bmp,pdf,JPG,JPEG,PNG,PDF|max:5120'
        ]);

        $check_quota = DB::table('karyawan_leave_quota')->where('karyawan_id', $request->nik)->where('leave_type_id', $request->leave_type_id)->count();
        // return $check_quota;
        if ($check_quota > 0) {
            $quota_id = DB::table('karyawan_leave_quota')->where('karyawan_id', $request->nik)->where('leave_type_id', $request->leave_type_id)->get(['id']);

            $cek = DB::table('karyawan_leave_trail')->where('karyawan_id', $request->nik)->where('leave_type_id', $request->leave_type_id)->whereBetween('leave_date', [$request->date_start, $request->date_end])->exists();
            if ($cek) {
                $tanggal = DB::table('karyawan_leave_trail')->where('karyawan_id', $request->nik)->where('leave_type_id', $request->leave_type_id)->whereBetween('leave_date', [$request->date_start, $request->date_end])->get(['leave_date']);
                return redirect('/karyawanleave/create')->with('status', 'Data Karyawan Leave sudah Ada');
            }

            if ($error) {
                $leave_quota = KaryawanLeaveQuota::where('is_taken', '=', '0')->where('karyawan_id', $request->nik)->where('leave_type_id', $request->leave_type_id)->count();
                if ($interval <= $leave_quota) {
                    //jika mencukupi
                    // return $leave_quota;
                    $karyawan_leave = new KaryawanLeave();
                    $mass_id = sprintf("%06", mt_rand(1, 999999));
                    // simpan foto
                    if ($files = $request->file('file')) {
                        $destinationPath = 'uploads/leave/'; // upload path
                        $profileImage = Str::slug($request->nik, '_') . "." . $files->getClientOriginalExtension();
                        $files->move($destinationPath, $profileImage);
                        $img = $profileImage;
                    }

                    $simpan_leave = [
                        'mass_id' => $mass_id,
                        'nik' => $request->nik,
                        'keterangan' => $request->keterangan,
                        'date_start' => $request->date_start,
                        'date_end' => $request->date_end,
                        'leave_type_id' => $request->leave_type_id,
                        'file' => $img,
                    ];

                    //save Karyawan_leave
                    $save = $karyawan_leave->create($simpan_leave);
                    // jika berhasil save
                    if ($save) {
                        $leave_id = KaryawanLeave::where('nik', $request->nik)
                            ->where('date_start', $request->date_start)
                            ->where('date_end', $request->date_end)
                            ->get(['id'])
                            ->first();
                        $save_trail = new KaryawanLeaveTrail();
                        //Validasi hari minggu dan libur
                        // get array master_libur
                        $master_libur = DB::table('master_libur')->whereBetween('tanggal', [$request->date_start, $request->date_end])->get(['tanggal']);
                        $tgl_libur = []; // membuat array tampungan master libur
                        foreach ($master_libur as $key) {
                            $tgl_libur[] = $key->tanggal;
                        }
                        // return $tgl_libur;
                        $data = [];
                        foreach ($period as $key => $date) {
                            // jika bukan hari minggu maka akan di insert
                            if ($date->format('l') != 'Sunday') {

                                // validasi jika tanggal tidak ada di master_libur
                                if (!in_array($date->format('Y-m-d'), $tgl_libur)) {
                                    $data =
                                        [
                                            'leave_id' => $leave_id->id,
                                            'karyawan_id' => $request->nik,
                                            'keterangan' => $request->keterangan,
                                            'leave_date' => $date->format('Y-m-d'),
                                            'leave_type_id' => $request->leave_type_id,
                                            'created_at' => Carbon::now()->toDateTimeString(),
                                            'created_by' => Auth::id(),
                                        ];
                                }

                                $save = $save_trail->create($data);
                            }
                        }
                        // jika berhasil simpan semua
                        if ($save)
                            return redirect('/karyawanleave')->with('status', 'Karyawan Leave Trail berhasil ditambahkan');
                        else
                            return redirect('/karyawanleave')->with('error', 'An error occurred');
                        // return $a;
                    }
                }
                // jika melebihi quota
                else {
                    return redirect('/karyawanleave/create')->with('status', 'Pengajuan Anda Melebihi kouta yang tersedia');
                }
            }

            // return $check_quota; 
        } else {
            return redirect('/karyawanleave/create')->with('status', 'Quota Karyawan Tidak Ada');
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
        $data['leave_type'] = LeaveType::all();
        $data['karyawan_leave_trail'] = KaryawanLeaveTrail::where('leave_id', $id)->get();
        // $data['leave_type_id'] = KaryawanLeave::where('mass_id', $id)->get(['id','nik','leave_type_id','date_start','date_end',]);
        // $data['start_date'] = KaryawanLeave::where('mass_id', $id)->min('date_start');
        // $data['end_date'] = KaryawanLeave::where('mass_id', $id)->max('date_end');
        $data['id'] = $id;
        return view('karyawanleave/show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(KaryawanLeave $karyawan_leave, $id, $trailid)
    {
        $data['karyawan'] = Employee::all();
        $data['master_leave_type'] = LeaveType::find($trailid);
        $data['karyawan_leave'] = KaryawanLeave::all();
        $data['id'] = $id;
        $data['karyawan_leave_trail'] = KaryawanLeaveTrail::find($trailid);
        // return $data['karyawan_leave_trail'];
        return view('karyawanleave/edit', $data);
        $karyawan_leave->updated_by = Auth::id();
        $karyawan_leave->updated_at = Carbon::now()->toDateTimeString();
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
        $start_date = Carbon::parse($request->input('date_start'));
        $end_date = Carbon::parse($request->input('date_end'));
        $period = CarbonPeriod::create($start_date, $end_date);

        $error = $request->validate([
            'status' => 'required',
            'keterangan' => 'required',
        ]);

        if ($error) {
            // $karyawan_leave= KaryawanLeaveTrail::find($id);
            // if ($files = $request->file('file')) {
            //     $destinationPath = 'uploads/leave/'; // upload path
            //     $profileImage = Str::slug($request->nik,'_') . "." . $files->getClientOriginalExtension();
            //     $files->move($destinationPath, $profileImage);
            //     $img= $profileImage;
            //  }
            $simpan_leave = [
                'status' => $request->status,
                'keterangan' => $request->keterangan,
                'updated_by' => Auth::id(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];


            //save Karyawan_leave
            // $save= $karyawan_leave->save($simpan_leave);
            $save = DB::table('karyawan_leave_trail')
                ->where('id', $id)
                ->update($simpan_leave);
            if ($save)
                return redirect('/karyawanleave')->with('success', 'Karyawan Leave Trail berhasil diUpdate');
            else
                return redirect('/karyawanleave')->with('error', 'An error occurred');
            // return $a;
            if ($save) {
                $leave_id = KaryawanLeave::where('nik', $request->nik)
                    ->where('date_start', $request->date_start)
                    ->where('date_end', $request->date_end)
                    ->get(['id'])
                    ->first();
                // return $leave_id->id;
                $trail = new KaryawanLeaveTrail();
                foreach ($period as $key => $date) {
                    $dates =
                        [
                            'karyawan_id' => $request->nik,
                            'leave_id' => $leave_id->id,
                            'leave_date' => $date->format('Y-m-d'),
                            'leave_type_id' => $request->get('leave_type_id'),
                            'updated_by' => Auth::id(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ];
                    // $a[] = $dates;
                    $savetrail = $trail->create($dates);
                }
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
        $karyawan_leave_trail = KaryawanLeaveTrail::find($id);
        $karyawan_leave = KaryawanLeave::find($id);
        $karyawan_leave_trail->deleted_by = Auth::id();
        $karyawan_leave_trail->deleted_at = Carbon::now()->toDateTimeString();

        if ($karyawan_leave->delete()) {
            return response()->json([
                'success' => 'Karyawan Leave Trail berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
    //     $karyawan_leave_trail = KaryawanLeaveTrail::find($id);
    //     $karyawan_leave_trail->deleted_by = Auth::id();
    //     $karyawan_leave_trail->deleted_at = Carbon::now()->toDateTimeString();
    //     $karyawan_leave_trail->save();
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'data berhasil di Delete',
    //     ]);
    // }

    // validasi periode 
    public function _validate_periode(Request $request)
    {
        Carbon::setLocale('id');
        $request->validate([
            'nik' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        if (KaryawanLeave::where('nik', $request->nik)->whereBetween('date', [$request->start_date, $request->end_date])->whereNull('deleted_at')->count() > 0) {
            $data = KaryawanLeave::where('nik', $request->nik)->whereBetween('date', [$request->start_date, $request->end_date])->whereNull('deleted_at')->get();
            return response()->json([
                'status' => false,
                'message' => 'Data sudah ada di database',
                'data' => $data,
            ]);
        }

        $start_date = Carbon::parse($request->input('start_date'));
        $end_date = Carbon::parse($request->input('end_date'));
        $data['total_day'] = $end_date->diffInDays($start_date) + 1;
        $data['day_name'] = $end_date->format('l');
        // generate periode selected
        $data['period'] = CarbonPeriod::create($start_date, $end_date);
        $dates = [];
        foreach ($data['period'] as $key => $date) {
            $dates[] = $date->format('l,Y-m-d');
        }
        $data['tanggal'] = $dates;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
