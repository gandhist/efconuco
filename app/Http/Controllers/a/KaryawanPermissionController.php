<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormValidation;
use App\KaryawanPermission;
use App\Rules\ValidasiLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class KaryawanPermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $filter_karyawan_id = $request->filter_karyawan_id;
            // $filter_is_fullday = $request->filter_is_fullday;
            $filter_permission_date = $request->filter_permission_date;
            $filter_start_date = $request->filter_start_date;



            // ***1***
            // ***filter_karyawan_id -> not empty***
            // ***filter_permission_date -> not empty***
            // ***filter_start_date -> not empty***
            if ($filter_karyawan_id && $filter_permission_date && $filter_start_date) {
                $data = DB::table('karyawan_permission AS kp')
                    ->select('ky.nama', 'kp.*')
                    ->whereNull('kp.deleted_at')
                    ->where('kp.karyawan_id', $filter_karyawan_id)
                    ->where('kp.permission_date', $filter_permission_date)
                    ->where('kp.start_date', $filter_start_date)
                    ->join('karyawan AS ky', 'kp.karyawan_id', '=', 'ky.id')
                    ->get();

                // ***2***
                // ***filter_karyawan_id -> not empty***
                // ***filter_permission_date -> not empty***
                // ***filter_start_date -> empty***
            } else if ($filter_karyawan_id && $filter_permission_date && !$filter_start_date) {
                $data = DB::table('karyawan_permission AS kp')
                    ->select('ky.nama', 'kp.*')
                    ->whereNull('kp.deleted_at')
                    ->where('kp.karyawan_id', $filter_karyawan_id)
                    ->where('kp.permission_date', $filter_permission_date)
                    ->join('karyawan AS ky', 'kp.karyawan_id', '=', 'ky.id')
                    ->get();

                // ***3***
                // ***filter_karyawan_id -> not empty***
                // ***filter_permission_date -> empty***
                // ***filter_start_date -> not empty***
            } else if ($filter_karyawan_id && !$filter_permission_date && $filter_start_date) {
                $data = DB::table('karyawan_permission AS kp')
                    ->select('ky.nama', 'kp.*')
                    ->whereNull('kp.deleted_at')
                    ->where('kp.karyawan_id', $filter_karyawan_id)
                    ->where('kp.start_date', $filter_start_date)
                    ->join('karyawan AS ky', 'kp.karyawan_id', '=', 'ky.id')
                    ->get();

                // ***4***
                // ***filter_karyawan_id -> not empty***
                // ***filter_permission_date -> empty***
                // ***filter_start_date -> empty***
            } else if ($filter_karyawan_id && !$filter_permission_date && !$filter_start_date) {
                $data = DB::table('karyawan_permission AS kp')
                    ->select('ky.nama', 'kp.*')
                    ->whereNull('kp.deleted_at')
                    ->where('kp.karyawan_id', $filter_karyawan_id)
                    ->join('karyawan AS ky', 'kp.karyawan_id', '=', 'ky.id')
                    ->get();


                // ***5***
                // ***all are empty***
            } else {
                $data = DB::table('karyawan_permission AS kp')
                    ->select('ky.nama', 'kp.*')
                    ->whereNull('kp.deleted_at')
                    ->join('karyawan AS ky', 'kp.karyawan_id', '=', 'ky.id')
                    ->get();
            }



            return datatables()->of($data)
                // ->setRowClass('{{ $id % 1 == 0 ? "alert-success" : "alert-warning" }}')
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $button = '<a href="karyawanpermission/' . $data->id . '/edit" class="btn btn-warning btn-xs"><span class="glyphicon glyphicon-pencil"></span>Edit</a>';
                    $button .= '&nbsp;&nbsp;';
                    $button .= '<form action="" id="confirmModal" method="post" class="form-group">';
                    $button .= '<button type="button" name="ok_button" id="ok_button" data-id="' . $data->id . '" class="btn btn-xs btn-danger delete"><span class="glyphicon glyphicon-trash"></span>Delete</button> </form>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $karyawanPermission = [];
        $kirims = DB::table('karyawan_permission AS kape')
            ->select('kape.karyawan_id AS nik', 'kywn.nama')
            ->whereNull('kape.deleted_at')
            ->join('karyawan AS kywn', 'kape.karyawan_id', '=', 'kywn.id')
            ->distinct()
            ->get();

        foreach ($kirims as $kirim) {
            $karyawanPermission[] = $kirim;
        }

        return view('karyawanpermission.index', compact('karyawanPermission'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $karyawan = DB::table('karyawan')->get();
        return view('karyawanpermission/create', ['karyawan' => $karyawan]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(StoreFormValidation $request)
    {
        // Validasi hari Libur dan Minggu pada variabel permission_date, start_date dan end_date
        $start_date = Carbon::parse($request->input('start_date'));
        $permission_date = Carbon::parse($request->input('permission_date'));
        $end_date = Carbon::parse($request->input('end_date'));

        //Create array untuk tanggal Libur Nasional
        $tgl_libur = [];
        $master_libur = DB::table('master_libur')->get(['tanggal']);
        foreach ($master_libur as $key) {
            $tgl_libur[] = $key->tanggal;
        }


        if (in_array($permission_date->format('Y-m-d'), $tgl_libur) || $permission_date->format('l') == 'Sunday') {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal ijin tidak boleh hari Minggu atau Libur Nasional'
            ], 422);
        }

        if (in_array($start_date->format('Y-m-d'), $tgl_libur) || $start_date->format('l') == 'Sunday') {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal mulai ijin tidak boleh hari Minggu atau Libur Nasional'
            ], 422);
        }

        if (in_array($end_date->format('Y-m-d'), $tgl_libur) || $end_date->format('l') == 'Sunday') {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal akhir ijin tidak boleh hari Minggu atau Libur Nasional'
            ], 422);
        }

        $is_fullday = $request->get('is_fullday');
        $permission_date = $request->get('permission_date');

        $permission = new KaryawanPermission();
        $nama = DB::table('karyawan')
            ->where('id', $request->get('karyawan_id'))
            ->value('nama');

        $getnama = explode(" ", $nama, 10);

        $permission->karyawan_id = $request->get('karyawan_id');
        $permission->keterangan = $request->get('keterangan');

        if ($is_fullday == null) {
            $permission->is_fullday = 0;
        } else {
            $permission->is_fullday = $is_fullday;
        }

        $permission->permission_date = $permission_date;
        $permission->start_date = $request->get('start_date');
        $permission->end_date = $request->get('end_date');
        $permission->start_hour = $request->get('start_hour');
        $permission->end_hour = $request->get('end_hour');
        $permission->latitude = $request->get('latitude');
        $permission->longitude = $request->get('longitude');

        if ($request->hasFile('file_permission')) {
            $file = $request->file('file_permission');
            $extension = $file->getClientOriginalExtension();
            $filename = $getnama[0] . '-' . time() . '.' . $extension;
            $file->move('uploads/permission/', $filename);
            $permission->file_permission = $filename;
        } else {
            $permission->file_permission = 'noletter.jpg';
        }

        $permission->status = $request->get('status');

        $permission->save();

        //Menyiapkan Insert Data to karyawan_permission_trail
        $lastId = DB::table('karyawan_permission')->orderBy('id', 'desc')->first();
        $permission_id = $lastId->id;
        $karyawan_id = $request->get('karyawan_id');
        $keterangan = $request->get('keterangan');
        $status = 0;

        //Jika ijin sehari penuh
        $durations = [];
        if ($permission_date == null) {
            $start_diff = Carbon::parse($request->get('start_date'));
            $finish_diff = Carbon::parse($request->get('end_date'));
            $durasi = $start_diff->diffInDays($finish_diff, false);

            for ($i = 0; $i <= $durasi; $i++) {
                $period = mktime(0, 0, 0, date("m", strtotime($start_diff)), date("d", strtotime($start_diff)) + $i, date("Y", strtotime($start_diff)));
                $period = date("Y-m-d", $period);
                $durations[] = $period;
            }
            //Check $durations still content Sunday or Holiday
            $durasi = [];
            foreach ($durations as $duration) {
                if (date('l', strtotime($duration)) != 'Sunday') {
                    $durasi[] = $duration;
                }
            }

            $periode = [];
            foreach ($durasi as $dur) {
                if (!in_array($dur, $tgl_libur)) {
                    $periode[] = $dur;
                }
            }

            foreach ($periode as $pr) {
                DB::table('karyawan_permission_trail')->insert([
                    'permission_id' => $permission_id,
                    'karyawan_id' => $karyawan_id,
                    'permission_date' => $pr,
                    'keterangan' => $keterangan,
                    'is_fullday' => $is_fullday,
                    'status' => $status,
                    'created_by' => Auth::id(),
                    'created_at' => Carbon::now()
                ]);
            }
        } else {
            // Jika ijin dalam 1 hari kerja

            if ($is_fullday == null) {
                $is_fullday = 0;
            }

            DB::table('karyawan_permission_trail')->insert([
                'permission_id' => $permission_id,
                'karyawan_id' => $karyawan_id,
                'permission_date' => $permission_date,
                'keterangan' => $keterangan,
                'is_fullday' => $is_fullday,
                'status' => $status,
                'created_by' => Auth::id(),
                'created_at' => Carbon::now()
            ]);
        }


        //Insert Data to karyawan_permission_log
        $permission_log = DB::table('karyawan_permission_trail')
            ->where('permission_id', $permission_id)
            ->get();

        foreach ($permission_log as $pl) {
            DB::table('karyawan_permission_log')->insert([
                'permission_trail_id' => $pl->id,
                'keterangan' => $keterangan,
                'status' => $status,
            ]);
        }

        // return redirect('/karyawanpermission')->with('status', 'Data Karyawan Ijin Berhasil Ditambahkan!');
        return response()->json([
            'status' => true,
            'message' => 'Data Karyawan Ijin Berhasil Ditambahkan!'
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\KaryawanPermission  $karyawanPermission
     * @return \Illuminate\Http\Response
     */
    public function show(KaryawanPermission $karyawanPermission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\KaryawanPermission  $karyawanPermission
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $karyawan = DB::table('karyawan_permission AS krp')
            ->select('krp.*', 'kry.nama')
            ->where('krp.id', $id)
            ->join('karyawan AS kry', 'krp.karyawan_id', '=', 'kry.id')
            ->get();
        // dd($karyawan);
        return view('karyawanpermission.edit', compact('karyawan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\KaryawanPermission  $karyawanPermission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // return $request;
        //All day or part of the day?
        if ($request->start_date != null) {
            $allday = true;
        }

        //Create array untuk tanggal Libur Nasional
        $tgl_libur = [];
        $master_libur = DB::table('master_libur')->get(['tanggal']);
        foreach ($master_libur as $key) {
            $tgl_libur[] = $key->tanggal;
        }

        // Get value of permission_date, start_date and end_date
        $permission_val = Carbon::parse($request->get('permission_date'));
        $start_val = Carbon::parse($request->get('start_date'));
        $end_val = Carbon::parse($request->get('end_date'));


        //Validate for prohibited on Sunday
        if ($allday) {
            if ($start_val->format('l') == 'Sunday') {
                return redirect()->back()->with('pesan', 'Tanggal Mulai Ijin tidak boleh hari Minggu!');
            } else if ($end_val->format('l') == 'Sunday') {
                return redirect()->back()->with('pesan', 'Tanggal Akhir Ijin tidak boleh hari Minggu!');
            }
        } else {
            if ($permission_val->format('l') == 'Sunday') {
                return redirect()->back()->with('pesan', 'Tanggal Ijin tidak boleh hari Minggu!');
            }
        }

        //Validate for prohibited on Holiday
        if ($allday) {
            if (in_array($start_val->format('Y-m-d'), $tgl_libur)) {
                return redirect()->back()->with('pesan', 'Tanggal Mulai Ijin tidak boleh hari Libur Nasional!');
            } else if (in_array($end_val->format('Y-m-d'), $tgl_libur)) {
                return redirect()->back()->with('pesan', 'Tanggal Selesai tidak boleh hari Libur Nasional!');
            }
        } else {
            if (in_array($permission_val->format('Y-m-d'), $tgl_libur)) {
                return redirect()->back()->with('pesan', 'Tanggal Ijin tidak boleh hari Libur Nasional!');
            }
        }

        $nama = DB::table('karyawan')
            ->where('id', $request->karyawan_id)
            ->value('nama');
        $getnama = explode(" ", $nama, 10);

        // return $request;
        // die;

        $karyawan_permission = KaryawanPermission::find($id)->get();
        // $karyawan_permission->karyawan_id = $request->get('karyawan_id');
        $old_file = $karyawan_permission[0]->file_permission;
        // dd($old_file);
        $karyawan_permission[0]->keterangan = $request->keterangan;

        if ($karyawan_permission[0]->permission_date == null) {
            $is_fullday = 1;
            $karyawan_permission[0]->is_fullday = $is_fullday;
            $karyawan_permission[0]->start_date = $request->start_date;
            $karyawan_permission[0]->end_date = $request->end_date;
        } else {
            $is_fullday = 0;
            $karyawan_permission[0]->is_fullday = $is_fullday;
            $karyawan_permission[0]->permission_date = $request->permission_date;
            $karyawan_permission[0]->start_hour = $request->start_hour;
            $karyawan_permission[0]->end_hour = $request->end_hour;
        }

        // $image_text = $request->file_permission;

        //Jika mengganti upload file, maka file lama dihapus
        if ($request->hasFile('file_permission')) {
            if ($old_file != 'noletter.jpg') {
                Storage::delete('uploads/permission/' . $karyawan_permission[0]->file_permission);
            }
            $file = $request->file('file_permission');
            $extension = $file->getClientOriginalExtension();
            $filename = $getnama[0] . '-' . time() . '.' . $extension;
            $file->move('uploads/permission/', $filename);
            $karyawan_permission[0]->file_permission = $filename;
        }

        $karyawan_permission[0]->status = $request->status;
        // $karyawan_permission[0]->updated_by = Auth::id();

        $karyawan_permission[0]->save();

        //Set status to cancelled for a while or temporary
        DB::table('karyawan_permission_trail')
            ->where('permission_id', $id)
            ->update([
                'status' => 2,
                'updated_by' => Auth::id(),
                'updated_at' => Carbon::now()
            ]);

        //Persiapan insert Data to karyawan_permission_log
        // $permission_log_temp = DB::table('karyawan_permission_trail')
        //     ->where('permission_id', $id)
        //     ->get();

        //Convert duration to each day begine from start_time
        // foreach ($permission_log_temp as $plt) {
        //     DB::table('karyawan_permission_log')->insert([
        //         'permission_trail_id' => $plt->id,
        //         'keterangan' => $plt->keterangan,
        //         'status' => $plt->status,
        //         'created_by' => Auth::id(),
        //         'created_at' => Carbon::now()
        //     ]);
        // }

        //Create Array from karyawan_permission_trail
        $arrOnTable = [];
        $totArr = DB::table('karyawan_permission_trail')
            ->where('permission_id', $id)
            ->get(['permission_date']);

        foreach ($totArr as $tot) {
            $arrOnTable[] = $tot->permission_date;
        }

        // dump($arrOnTable);

        //Create Array from Request
        $arrOnRequest = [];
        $arrResult = [];
        if ($is_fullday == 1) {
            $start_time = Carbon::parse($request->get('start_date'));
            $finish_time = Carbon::parse($request->get('end_date'));
            $durasi = $start_time->diffInDays($finish_time, false);

            for ($i = 0; $i <= $durasi; $i++) {
                $period = mktime(0, 0, 0, date("m", strtotime($start_time)), date("d", strtotime($start_time)) + $i, date("Y", strtotime($start_time)));
                $period = date("Y-m-d", $period);
                $arrOnRequest[] = $period;
            }

            // bersihkan $arrOnRequest dari hari Minggu
            $durasi = [];
            foreach ($arrOnRequest as $result) {
                if (date('l', strtotime($result)) != 'Sunday') {
                    $durasi[] = $result;
                }
            }

            // bersihkan $durasi dari hari Libur Nasional
            $arrResult = [];
            foreach ($durasi as $dur) {
                if (!in_array($dur, $tgl_libur)) {
                    $arrResult[] = $dur;
                }
                // else {
                //     $arrResult[] = $request->get('permission_date');
                // }
            }

            // dump($arrResult);

            //Match $arrResult Array with $arrOnTable Array
            if ($arrOnTable > $arrResult) {
                $arrEdit = array_intersect($arrOnTable, $arrResult);
            } else {
                $arrEdit = array_intersect($arrResult, $arrOnTable);
            }

            // dump($arrEdit);

            //Unmatched $arrResult Array with $arrOnTable untuk ditambah ke tabel karyawan_permission_trail
            $arrDiff = array_merge(array_diff($arrOnTable, $arrResult), array_diff($arrResult, $arrOnTable));

            // dump($arrDiff);

            //Create Array that will insert to karyawan_permission_trail
            $arrInsert = array_diff($arrDiff, $arrOnTable);

            // dd($arrInsert);

            //Update dan Insert data karyawan_permission_trail
            $permission_id = $id;
            $karyawan_id = $request->get('karyawan_id');
            $keterangan = $request->get('keterangan');
            $status = 0;

            //Update status karyawan_permission_trail
            if ($arrEdit != null) {
                foreach ($arrEdit as $ae) {
                    DB::table('karyawan_permission_trail')
                        ->where('permission_id', $id)
                        ->where('permission_date', $ae)
                        ->update([
                            'permission_id' => $permission_id,
                            'karyawan_id' => $karyawan_id,
                            'permission_date' => $ae,
                            'keterangan' => $keterangan,
                            'is_fullday' => $is_fullday,
                            'status' => $status,
                            'updated_by' => Auth::id()
                        ]);
                }
            }

            //Insert Data to karyawan_permission_log
            $permission_log_edit = DB::table('karyawan_permission_trail')
                ->where('permission_id', $id)
                ->get();

            foreach ($permission_log_edit as $ple) {
                DB::table('karyawan_permission_log')->insert([
                    'permission_trail_id' => $ple->id,
                    'keterangan' => $ple->keterangan,
                    'status' => $ple->status,
                    'created_by' => Auth::id()
                ]);
            }


            //Insert Data to karyawan_permission trail
            if ($arrInsert != null) {
                foreach ($arrInsert as $ins) {
                    DB::table('karyawan_permission_trail')
                        ->where('permission_id', $id)
                        ->where('permission_date', $ins)
                        ->insert([
                            'permission_id' => $permission_id,
                            'karyawan_id' => $karyawan_id,
                            'permission_date' => $ins,
                            'keterangan' => $keterangan,
                            'is_fullday' => $is_fullday,
                            'status' => $status,
                            'created_by' => Auth::id(),
                            'created_at' => Carbon::now()
                        ]);
                }
            }

            //Insert Data to karyawan_permission_log
            $permission_log_ins = DB::table('karyawan_permission_trail')
                ->where('permission_id', $permission_id)
                ->get();

            foreach ($permission_log_ins as $pli) {
                if (in_array($pli->permission_date, $arrInsert, true)) {
                    DB::table('karyawan_permission_log')->insert([
                        'permission_trail_id' => $pli->id,
                        'keterangan' => $pli->keterangan,
                        'status' => $pli->status,
                        'created_by' => Auth::id(),
                        'created_at' => Carbon::now()
                    ]);
                }
            }

            return redirect('/karyawanpermission')->with('status', 'Data Karyawan Ijin Berhasil Diubah!');
        }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\KaryawanPermission  $karyawanPermission
     * @return \Illuminate\Http\Response
     */
    public function destroy(KaryawanPermission $karyawanPermission, $id)
    {
        KaryawanPermission::where('id', $id)
            ->update([
                'status' => 2,
                'deleted_by' => Auth::id()
            ]);

        KaryawanPermission::destroy($id);

        DB::table('karyawan_permission_trail')
            ->where('permission_id', $id)
            ->update([
                'status' => 2,
                'updated_by' => Auth::id(),
                'updated_at' => Carbon::now()
            ]);

        //Insert Data to karyawan_permission_log
        $permission_log_trail = DB::table('karyawan_permission_trail')
            ->where('permission_id', $id)
            ->get();

        foreach ($permission_log_trail as $plt) {
            DB::table('karyawan_permission_log')->insert([
                'permission_trail_id' => $plt->id,
                'keterangan' => $plt->keterangan,
                'status' => $plt->status,
                'created_by' => Auth::id()
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Karyawan Ijin berhasil di hapus!'
        ]);
    }
}
