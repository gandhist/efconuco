<?php

namespace App\Http\Controllers;

use App\Sakit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\SakitFormValidation;
use Illuminate\Support\Facades\Auth;

class SakitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            if (!empty($request->filter_nik) && (!empty($request->filter_date_start) && (!empty($request->filter_date_end)))) {
                $data = DB::table('karyawan_sakit AS ks')
                    ->select('ky.nama', 'ks.*')
                    ->whereNull('ks.deleted_at')
                    ->where('ks.karyawan_id', $request->filter_nik)
                    ->where('ks.date_start', $request->filter_date_start)
                    ->where('ks.date_end', $request->filter_date_end)
                    ->join('karyawan AS ky', 'ks.karyawan_id', '=', 'ky.id')
                    ->get();
            } 
            else if(!empty($request->filter_nik) && (!empty($request->filter_date_start) && (empty($request->filter_date_end)))){
                $data = DB::table('karyawan_sakit AS ks')
                    ->select('ky.nama', 'ks.*')
                    ->whereNull('ks.deleted_at')
                    ->where('ks.karyawan_id', $request->filter_nik)
                    ->where('ks.date_start', $request->filter_date_start)
                    ->join('karyawan AS ky', 'ks.karyawan_id', '=', 'ky.id')
                    ->get();
            }
            else if(!empty($request->filter_nik) && (empty($request->filter_date_start) && (empty($request->filter_date_end)))){
                $data = DB::table('karyawan_sakit AS ks')
                    ->select('ky.nama', 'ks.*')
                    ->whereNull('ks.deleted_at')
                    ->where('ks.karyawan_id', $request->filter_nik)
                    ->join('karyawan AS ky', 'ks.karyawan_id', '=', 'ky.id')
                    ->get();
            }
            else {
                $data = DB::table('karyawan_sakit AS ks')
                    ->select('ky.nama', 'ks.*')
                    ->whereNull('ks.deleted_at')
                    ->join('karyawan AS ky', 'ks.karyawan_id', '=', 'ky.id')
                    ->get();
            }

            return datatables()->of($data)
                // ->setRowClass('{{ $id % 1 == 0 ? "alert-success" : "alert-warning" }}')
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $button = '<a href="sakit/' . $data->id . '/edit" class="btn btn-warning btn-xs"><span class="glyphicon glyphicon-pencil"></span>Edit</a>';
                    $button .= '&nbsp;&nbsp;';
                    $button .= '<form action="" id="confirmModal" method="post" class="form-group">';
                    $button .= '<button type="button" name="ok_button" id="ok_button" data-id="' . $data->id . '" class="btn btn-xs btn-danger delete"><span class="glyphicon glyphicon-trash"></span>Delete</button> </form>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $sakit = [];
        $kirims = DB::table('karyawan_sakit AS sakit')
            ->select('sakit.karyawan_id', 'karyawan.nama')
            ->join('karyawan', 'sakit.karyawan_id', '=', 'karyawan.id')
            ->distinct()
            ->get();

        foreach ($kirims as $kirim) {
            $sakit[] = $kirim;
        }
 
        return view('sakit.index', compact('sakit'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $karyawan = DB::table('karyawan')->get();
        return view('sakit/create', compact('karyawan'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SakitFormValidation $request)
    {
        // Validasi hari Libur dan Minggu pada variabel permission_date, start_date dan end_date
        $start_time = Carbon::parse($request->get('date_start'));
        $finish_time = Carbon::parse($request->get('date_end'));

        //Create array untuk tanggal Libur Nasional
        $tgl_libur = [];
        $master_libur = DB::table('master_libur')->get(['tanggal']);
        foreach ($master_libur as $key) {
            $tgl_libur[] = $key->tanggal;
        }

        if (in_array($start_time->format('Y-m-d'), $tgl_libur) || $start_time->format('l') == 'Sunday') {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal Mulai tidak boleh hari Minggu atau Libur Nasional'
            ], 422);
        }

        if (in_array($finish_time->format('Y-m-d'), $tgl_libur) || $finish_time->format('l') == 'Sunday') {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal Akhir tidak boleh hari Minggu atau Libur Nasional'
            ], 422);
        }

        $sakit = new Sakit();
        $nama = DB::table('karyawan')
            ->where('id', $request->get('karyawan_id'))
            ->value('nama');

        $getnama = explode(" ", $nama, 10);

        $sakit->karyawan_id = $request->get('karyawan_id');
        $sakit->keterangan = $request->get('keterangan');
        $sakit->date_start = $request->get('date_start');
        $sakit->date_end = $request->get('date_end');

        if ($request->hasFile('file_sakit')) {
            $file = $request->file('file_sakit');
            $extension = $file->getClientOriginalExtension();
            $filename = $getnama[0] . '-' . time() . '.' . $extension;
            $file->move('uploads/sakit/', $filename);
            $sakit->file_sakit = $filename;
        } else {
            $sakit->file_sakit = 'noletter.jpg';
        }

        $sakit->save();

        //Menyiapkan Insert Data to karyawan_sakit_trail
        $lastId = DB::table('karyawan_sakit')->orderBy('id', 'desc')->first();
        $karyawan_id = $request->get('karyawan_id');
        $keterangan = $request->get('keterangan');
        $sakit_id = $lastId->id;
        $status = 0;

        //Mengambil durasi hari sakit
        $durasi = $start_time->diffInDays($finish_time, false);

        for ($i = 0; $i <= $durasi; $i++) {
            $period = mktime(0, 0, 0, date("m", strtotime($start_time)), date("d", strtotime($start_time)) + $i, date("Y", strtotime($start_time)));
            $period = date("Y-m-d", $period);
            $periods[] = $period;
        }

        //Check $periods, delete array that consists of Sunday
        $durasi = [];
        foreach ($periods as $duration) {
            if (date('l', strtotime($duration)) != 'Sunday') {
                $durasi[] = $duration;
            }
        }

        //Check $durasi, delete array that consist of Holiday
        $periode = [];
        foreach ($durasi as $dur) {
            if (!in_array($dur, $tgl_libur)) {
                $periode[] = $dur;
            }
        }

        foreach ($periode as $p) {
            DB::table('karyawan_sakit_trail')->insert([
                'karyawan_id' => $karyawan_id,
                'sakit_id' => $sakit_id,
                'date' => $p,
                'status' => $status,
                'keterangan' => $keterangan,
                'created_by' => Auth::id(),
                'created_at' => Carbon::now()
            ]);
        }

        //Insert Data to karyawan_permission_log
        $permission_log = DB::table('karyawan_sakit_trail')
            ->where('karyawan_id', $karyawan_id)
            ->where('sakit_id', $sakit_id)
            ->get();

        foreach ($permission_log as $pl) {
            DB::table('karyawan_sakit_log')->insert([
                'sakit_trail_id' => $pl->id,
                'status' => $pl->status,
                'keterangan' => $pl->keterangan,
                'created_by' => Auth::id(),
                'created_at' => Carbon::now()

            ]);
        }

        // return redirect('/karyawanpermission')->with('status', 'Data Karyawan Ijin Berhasil Ditambahkan!');
        return response()->json([
            'status' => true,
            'message' => 'Data Karyawan Sakit Berhasil Ditambahkan!'
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Sakit  $sakit
     * @return \Illuminate\Http\Response
     */
    public function show(Sakit $sakit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sakit  $sakit
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $sakit = DB::table('karyawan_sakit AS kst')
            ->select('kst.*', 'kry.nama')
            ->where('kst.id', $id)
            ->join('karyawan AS kry', 'kst.karyawan_id', '=', 'kry.id')
            ->get();
        return view('sakit.edit', compact('sakit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sakit  $sakit
     * @return \Illuminate\Http\Response
     */
    public function update(SakitFormValidation $request)
    {
        $sakit_id = $request->get('id');
        $karyawan_id = $request->get('karyawan_id');

        //Create array for Holiday
        $tgl_libur = [];
        $master_libur = DB::table('master_libur')->get(['tanggal']);
        foreach ($master_libur as $key) {
            $tgl_libur[] = $key->tanggal;
        }

        // Get value of start_date and end_date
        $start_date = Carbon::parse($request->get('date_start'));
        $end_date = Carbon::parse($request->get('date_end'));

        //Validate start_date and end_date for Sunday
        if ($start_date->format('l') == 'Sunday') {
            return redirect()->back()->with('pesan', 'Tanggal Mulai tidak boleh hari Minggu!');
        } else if ($end_date->format('l') == 'Sunday') {
            return redirect()->back()->with('pesan', 'Tanggal Selesai tidak boleh hari Minggu!');
        }

        //Validate start_date and end_date for Holiday
        if (in_array($start_date->format('Y-m-d'), $tgl_libur)) {
            return redirect()->back()->with('pesan', 'Tanggal Mulai tidak boleh hari Libur Nasional!');
        } else if (in_array($end_date->format('Y-m-d'), $tgl_libur)) {
            return redirect()->back()->with('pesan', 'Tanggal Selesai tidak boleh hari Libur Nasional!');
        }

        $nama = DB::table('karyawan')
            ->where('id', $request->get('karyawan_id'))
            ->value('nama');
        $getnama = explode(" ", $nama, 10);

        $sakit = Sakit::find($sakit_id);
        $old_file = $sakit['file_sakit'];
        $sakit->karyawan_id = $request->get('karyawan_id');
        $sakit->keterangan = $request->get('keterangan');
        $sakit->date_start = $request->get('date_start');
        $sakit->date_end = $request->get('date_end');

        //Jika mengganti upload file, maka file lama dihapus
        if ($request->hasFile('file_sakit')) {
            if ($old_file != 'noletter.jpg') {
                Storage::delete('uploads/sakit/' . $sakit->file_sakit);
            }
            $file = $request->file('file_sakit');
            $extension = $file->getClientOriginalExtension();
            $filename = $getnama[0] . '-' . time() . '.' . $extension;
            $file->move('uploads/sakit/', $filename);
            $sakit->file_sakit = $filename;
        }

        $sakit->save();

        //Set status to cancelled for a while or temporary
        DB::table('karyawan_sakit_trail')
            ->where('sakit_id', $sakit_id)
            ->update(['status' => 2]);

        // //Prepare insert Data to karyawan_sakit_log
        // $sakit_log_temp = DB::table('karyawan_sakit_trail')
        //     ->where('sakit_id',  $sakit_id)
        //     ->get();

        // //Insert to karyawan_sakit_log based on karyawan_sakit_temp
        // foreach ($sakit_log_temp as $slt) {
        //     DB::table('karyawan_sakit_log')->insert([
        //         'sakit_trail_id' => $slt->id,
        //         'status' => $slt->status,
        //         'keterangan' => $slt->keterangan,
        //         'created_by' => Auth::id(),
        //         'created_at' => Carbon::now()
        //     ]);
        // }

        // DB::table('karyawan_sakit_trail')->where('sakit_id', $request->get('id'))->delete();

        //Preparation to insert or update data to karyawan_sakit_trail

        $status = 0;
        $keterangan = $request->get('keterangan');

        $start_time = Carbon::parse($request->get('date_start'));
        $finish_time = Carbon::parse($request->get('date_end'));
        $durasi = $start_time->diffInDays($finish_time, false);

        //Convert duration to each day begining from start_time
        $arrOnRequest = [];
        $arrResult = [];
        for ($i = 0; $i <= $durasi; $i++) {
            $period = mktime(0, 0, 0, date("m", strtotime($start_time)), date("d", strtotime($start_time)) + $i, date("Y", strtotime($start_time)));
            $period = date("Y-m-d", $period);
            $arrOnRequest[] = $period;
        }

        // Delete from request array if Sunday
        $durasi = [];
        foreach ($arrOnRequest as $result) {
            if (date('l', strtotime($result)) != 'Sunday') {
                $durasi[] = $result;
            }
        }

        //Delete from request array if Holiday
        foreach ($durasi as $dur) {
            if (!in_array($dur, $tgl_libur)) {
                $arrResult[] = $dur;
            }
        }

        //Create Array from karyawan_sakit_trail
        $arrOnTable = [];
        $totArr = DB::table('karyawan_sakit_trail')
            ->where('sakit_id', $sakit_id)
            ->get(['date']);

        foreach ($totArr as $tot) {
            $arrOnTable[] = $tot->date;
        }

        //Match $arrResult Array with $arrOnTable Array
        $arrEdit = [];
        if ($arrOnTable > $arrResult) {
            $arrEdit = array_intersect($arrOnTable, $arrResult);
        } else {
            $arrEdit = array_intersect($arrResult, $arrOnTable);
        }

        //Update status karyawan_permission_trail
        if ($arrEdit != null) {
            foreach ($arrEdit as $ae) {
                DB::table('karyawan_sakit_trail')
                    ->where('karyawan_id', $karyawan_id)
                    ->where('date', $ae)
                    ->update([
                        'sakit_id' => $sakit_id,
                        'date' => $ae,
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'updated_by' => Auth::id(),
                        'updated_at' => Carbon::now()
                    ]);
            }
        }

        //Prepare Update Data to karyawan_sakit_log
        $sakit_log_update = DB::table('karyawan_sakit_trail')
            ->where('karyawan_id', $karyawan_id)
            ->where('sakit_id', $sakit_id)
            ->get();

        //Insert to karyawan_sakit_log based on karyawan_sakit_insert
        foreach ($sakit_log_update as $sli) {
            if ($sli->status != 0) {
                DB::table('karyawan_sakit_log')->insert([
                    'sakit_trail_id' => $sli->id,
                    'status' => $sli->status,
                    'keterangan' => $sli->keterangan,
                    'created_by' => Auth::id(),
                    'created_at' => Carbon::now()
                ]);
            }
        }

        //Unmatched $arrResult Array with $arrOnTable untuk ditambah ke tabel karyawan_permission_trail
        $arrDiff = [];
        $arrDiff = array_merge(array_diff($arrOnTable, $arrResult), array_diff($arrResult, $arrOnTable));

        //Create Array that will insert to karyawan_permission_trail
        $arrInsert = [];
        $arrInsert = array_diff($arrDiff, $arrOnTable);

        //Insert result to karyawan_sakit_trail
        foreach ($arrInsert as $p) {
            DB::table('karyawan_sakit_trail')->insert([
                'karyawan_id' => $request->get('karyawan_id'),
                'sakit_id' => $sakit_id,
                'date' => $p,
                'status' => 0,
                'keterangan' => $keterangan,
                'created_by' => Auth::id(),
                'created_at' => Carbon::now()
            ]);
        }

        //Prepare insert Data to karyawan_sakit_log
        $sakit_log_insert = DB::table('karyawan_sakit_trail')
            ->where('sakit_id', $sakit_id)
            ->get();

        //Insert to karyawan_sakit_log based on karyawan_sakit_insert
        foreach ($sakit_log_insert as $slog) {
            if ($slog->status == 0) {
                DB::table('karyawan_sakit_log')->insert([
                    'sakit_trail_id' => $slog->id,
                    'status' => $slog->status,
                    'keterangan' => $slog->keterangan,
                    'created_by' => Auth::id(),
                    'created_at' => Carbon::now()
                ]);
            }
        }

        return redirect('/sakit')->with('status', 'Data Karyawan Sakit Berhasil Diubah!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sakit  $sakit
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Sakit::where('id', $id)
            ->update(['deleted_by' => Auth::id()]);

        Sakit::destroy($id);

        //Update karyawan_sakit_trail after softcopy karyawan_sakit
        DB::table('karyawan_sakit_trail')
            ->where('sakit_id', $id)
            ->update([
                'status' => 2,
                'updated_by' => Auth::id(),
                'updated_at' => Carbon::now()
            ]);

        //Insert Data to karyawan_sakit_log
        $sakit_log_trail = DB::table('karyawan_sakit_trail')
            ->where('sakit_id', $id)
            ->get();

        foreach ($sakit_log_trail as $plt) {
            DB::table('karyawan_sakit_log')->insert([
                'sakit_trail_id' => $plt->id,
                'status' => 2,
                'keterangan' => $plt->keterangan,
                'created_by' => Auth::id(),
                'created_at' => Carbon::now()
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Karyawan Sakit berhasil di hapus!'
        ]);
    }
}
