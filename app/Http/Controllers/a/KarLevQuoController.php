<?php

namespace App\Http\Controllers;

use App\KaryawanLeaveQuota;
use \App\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KarLevQuoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["karyawan_leave_quota"] = KaryawanLeaveQuota::all();
        $data["karlevGrouped"] = KaryawanLeaveQuota::select('id','leave_type_id','karyawan_id','keterangan',DB::raw('count(is_taken) as qouta'))
        ->where('is_taken',0)
        ->groupBy('leave_type_id','karyawan_id')
        ->get();
        return view('karyawanleavequota/index')->with($data);
        // return $leave_quota;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data["dd_emp"] = Employee::all();
        $data['dd_karyawan_leave_quota'] = KaryawanLeaveQuota::all();
        return view('karyawanleavequota/create',$data);
        $karyawan_leave_quota->created_by = Auth::id();
        $karyawan_leave_quota->created_at = Carbon::now()->toDateTimeString();
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
            'is_taken' => 'required',
            'keterangan' => 'required',
            'leave_date' => 'required',
        ]);

        if ($error) {$karyawan_leave_quota= new KaryawanLeaveQuota();
        $karyawan_leave_quota->nik= $request->get('nik');
        $karyawan_leave_quota->is_taken= $request->get('is_taken');
        $karyawan_leave_quota->keterangan= $request->get('keterangan');
        $karyawan_leave_quota->leave_date= $request->get('leave_date');
        $karyawan_leave_quota->created_by = Auth::id();
        if($karyawan_leave_quota->save())
            return redirect('/karyawanleavequota')->with('success', 'Karyawan Leave berhasil ditambahkan');
        else
            return redirect('/karyawanleavequota')->with('error', 'An error occurred');
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\KaryawanLeaveQuota
     * @return \Illuminate\Http\Response
     */
    public function show(KaryawanLeaveQuota $karyawan_leave_quota)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\KaryawanLeaveQuota
     * @return \Illuminate\Http\Response
     */
    public function edit(KaryawanLeaveQuota $karyawan_leave_quota,$id)
    {
        $data["dd_karyawan_leave_quota"]= KaryawanLeaveQuota::all();
        $data["dd_emp"] = Employee::all();
        $data['karyawan_leave_quota'] = $karyawan_leave_quota::find($id);
        return view('karyawanleavequota/edit',$data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\KaryawanLeaveQuota
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'nik' => 'required',
            'is_taken' => 'required',
            'keterangan' => 'required',
            'leave_date' => 'required',
        ]);
        $karyawan_leave_quota= KaryawanLeaveQuota::find($id);
        $karyawan_leave_quota->nik= $request->get('nik');
        $karyawan_leave_quota->is_taken= $request->get('is_taken');
        $karyawan_leave_quota->keterangan= $request->get('keterangan');
        $karyawan_leave_quota->leave_date= $request->get('leave_date');
        $karyawan_leave_quota->updated_by = Auth::id();

        if($karyawan_leave_quota->save())
            return redirect('/karyawanleavequota')->with('success', 'Karyawan Leave Quota berhasil diupdate');
        else
            return redirect('/karyawanleavequota')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\KaryawanLeaveQuota
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $karyawan_leave_quota = KaryawanLeaveQuota::find($id);
        $karyawan_leave_quota->deleted_by = Auth::id();
        $karyawan_leave_quota->deleted_at = Carbon::now()->toDateTimeString();

        if($karyawan_leave_quota->save()){
            return response()->json([
                'success' => 'Karyawan Leave berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }

    public function chained_provinsi_kota(Request $request){
       
        if ($request->provinsi) {
            return $data = DB::table('master_kota')
                ->where('provinsi_id', '=', $request->provinsi)
                ->get(['id','nama as text']);
        }
        else {
            return $data = DB::table('master_kota')
                ->where('id', '=', $request->kota)
                ->get(['provinsi_id']);
        }
    }
}
