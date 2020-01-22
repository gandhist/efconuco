<?php

namespace App\Http\Controllers;

use App\KaryawanLeave;
use App\KaryawanLeaveLog;
use App\KaryawanLeaveTrail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KarLevLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["karyawan_leave_log"] = KaryawanLeaveLog::all();
        //dd($data);
        return view('karyawanleavelog/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['dd_karyawan_leave_log'] = KaryawanLeaveLog::all();
        $data['dd_karyawan_leave_trail'] = KaryawanLeaveTrail::all();
        return view('karyawanleavelog/create',$data);
        $karyawan_leave_log->created_by = Auth::id();
        $karyawan_leave_log->created_at = Carbon::now()->toDateTimeString();
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
            'leave_id' => 'required',
            'trail_id' => 'required',
            'status' => 'required',
        ]);

        if ($error) {$karyawan_leave_log= new KaryawanLeaveLog();
        $karyawan_leave_log->leave_id= $request->get('leave_id');
        $karyawan_leave_log->trail_id= $request->get('trail_id');;
        $karyawan_leave_log->status= $request->get('status');
        $karyawan_leave_log->created_at = Carbon::now()->toDateTimeString();
        if($karyawan_leave_log->save())
            return redirect('/karyawanleavelog')->with('success', 'Karyawan Leave Log berhasil ditambahkan');
        else
            return redirect('/karyawanleavelog')->with('error', 'An error occurred');
    }

}

    /**
     * Display the specified resource.
     *
     * @param  \App\KaryawanLeaveLog
     * @return \Illuminate\Http\Response
     */
    public function show(KaryawanLeaveLog $karyawan_leave_log)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\KaryawanLeaveLog
     * @return \Illuminate\Http\Response
     */
    public function edit(KaryawanLeaveLog $karyawan_leave_log,$id)
    {
        $data['dd_karyawan_leave'] = KaryawanLeave::all();
        $data['dd_karyawan_leave_log'] = KaryawanLeaveLog::all();
        $data['dd_karyawan_leave_trail'] = KaryawanLeaveTrail::all();
        $data['karyawan_leave_log'] = $karyawan_leave_log::find($id);
        return view('karyawanleavelog/edit',['dd'=> $dd_karyawan_leave_log])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\KaryawanLeaveLog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'leave_id' => 'required',
            'trail_id' => 'required',
            'status' => 'required',
        ]);
        $karyawan_leave_log= KaryawanLeaveLog::find($id);
        $karyawan_leave_log->leave_id= $request->get('leave_id');
        $karyawan_leave_log->trail_id= $request->get('trail_id');
        $karyawan_leave_log->status= $request->get('status');
        // $karyawan_leave_log->updated_by = Auth::id();

        if($karyawan_leave_log->save())
            return redirect('/karyawanleavelog')->with('success', 'Karyawan Leave Log berhasil diupdate');
        else
            return redirect('/karyawanleavelog')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\KaryawanLeaveLog
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $karyawan_leave_log = KaryawanLeaveLog::find($id);
        // $karyawan_leave_log->deleted_by = Auth::id();
        // $karyawan_leave_log->deleted_at = Carbon::now()->toDateTimeString();

        if($karyawan_leave_log->delete()){
            return response()->json([
                'success' => 'Karyawan Leave Log berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }

}
