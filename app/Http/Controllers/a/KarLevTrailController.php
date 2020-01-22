<?php

namespace App\Http\Controllers;

use App\KaryawanLeaveTrail;
use App\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KarLevTrailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["karyawan_leave_trail"] = KaryawanLeaveTrail::all();
        return view('karyawanleavetrail/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['dd'] = Employee::all();
        $data['dd_karyawan_leave_trail'] = KaryawanLeaveTrail::all();
        return view('karyawanleavetrail/create',$data);
        $karyawan_leave_trail->created_by = Auth::id();
        $karyawan_leave_trail->created_at = Carbon::now()->toDateTimeString();
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
            'leave_id' => 'required',
            'leave_date' => 'required',
            'leave_quota_id' => 'required',
            'status' => 'required',
        ]);

        if ($error) {$karyawan_leave_trail= new KaryawanLeaveTrail();
        $karyawan_leave_trail->leave_id= $request->get('leave_id');
        $karyawan_leave_trail->leave_date= $request->get('leave_date');
        $karyawan_leave_trail->leave_quota_id= $request->get('leave_quota_id');
        $karyawan_leave_trail->status= $request->get('status');
        $karyawan_leave_trail->created_at = Carbon::now()->toDateTimeString();
        if($karyawan_leave_trail->save())
            return redirect('/karyawanleavetrail')->with('success', 'Karyawan Leave Trail berhasil ditambahkan');
        else
            return redirect('/karyawanleavetrail')->with('error', 'An error occurred');
    }

}

    /**
     * Display the specified resource.
     *
     * @param  \App\KaryawanLeaveTrail
     * @return \Illuminate\Http\Response
     */
    public function show(KaryawanLeaveTrail $karyawan_leave_trail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\KaryawanLeaveTrail
     * @return \Illuminate\Http\Response
     */
    public function edit(KaryawanLeaveTrail $karyawan_leave_trail,$id)
    {
        $data['dd'] = Employee::all();
        $dd_karyawan_leave_trail= KaryawanLeaveTrail::all();
        $data['karyawan_leave_trail'] = $karyawan_leave_trail::find($id);
        return view('karyawanleavetrail/edit',['dd'=> $dd_karyawan_leave_trail])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\KaryawanLeaveTrail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // return $request;
        $error = $request->validate([
            'leave_id' => 'required',
            'leave_date' => 'required',
            'keterangan' => 'required',
            'status' => 'required',
        ]);
        $karyawan_leave_trail= KaryawanLeaveTrail::find($id);
        // $karyawan_leave_trail->leave_id= $request->get('leave_id');
        // $karyawan_leave_trail->leave_date= $request->get('leave_date');
        // $karyawan_leave_trail->leave_quota_id= $request->get('leave_quota_id');
        $karyawan_leave_trail->keterangan= $request->get('keterangan');
        $karyawan_leave_trail->status= $request->get('status');
        $karyawan_leave_trail->updated_by = Auth::id();
        $karyawan_leave_trail->updated_at = Carbon::now()->toDateTimeString();
        if($karyawan_leave_trail->save())
            return redirect('/karyawanleavetrail')->with('success', 'Karyawan Leave Trail berhasil diupdate');
        else
            return redirect('/karyawanleavetrail')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\KaryawanLeaveTrail
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $karyawan_leave_trail = KaryawanLeaveTrail::find($id);
        $karyawan_leave_trail->deleted_by = Auth::id();
        $karyawan_leave_trail->deleted_at = Carbon::now()->toDateTimeString();

        if($karyawan_leave_trail->delete()){
            return response()->json([
                'success' => 'Karyawan Leave Trail berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }

}
