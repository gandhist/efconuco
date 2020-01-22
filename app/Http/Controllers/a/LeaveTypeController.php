<?php

namespace App\Http\Controllers;

use App\LeaveType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_leave_type"] = LeaveType::all();
        return view('leavetype/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $leavetype= LeaveType::all();
        return view('leavetype/create',['dd'=> $leavetype]);
        $master_leave_type->created_at = Carbon::now()->toDateTimeString();
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
            'nama_cuti' => 'required',
            'rate' => 'required',
            'keterangan' => 'required',
        ]);

        if ($error) {
            $master_leave_type = new LeaveType;
            $master_leave_type->nama_cuti = $request->nama_cuti;
            $master_leave_type->rate = $request->rate;
            $master_leave_type->keterangan = $request->keterangan;
            if($master_leave_type->save())
            return redirect('/leavetype')->with('success', 'Provinsi berhasil ditambahkan');
            else
            return redirect('/leavetype')->with('error', 'An error occurred');};
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\LeaveType
     * @return \Illuminate\Http\Response
     */
    public function show(LeaveType $master_leave_type)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\LeaveType
     * @return \Illuminate\Http\Response
     */
    public function edit(LeaveType $master_leave_type,$id)
    {
        $leavetype= LeaveType::all();
        $data['master_leave_type'] = $master_leave_type::find($id);
        return view('leavetype/edit',['dd'=> $leavetype])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provinsi
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'nama_cuti' => 'required',
            'rate' => 'required',
            'keterangan' => 'required',
        ]);

        if ($error){
        $master_leave_type= LeaveType::find($id);
        $master_leave_type->nama_cuti = $request->get('nama');
        $master_leave_type->rate = $request->get('rate');
        $master_leave_type->keterangan = $request->get('keterangan');
        $master_leave_type->updated_at = Carbon::now()->toDateTimeString();
        if($master_leave_type->save())
            return redirect('/leavetype')->with('success', 'Provinsi berhasil diupdate');
        else
            return redirect('/leavetype')->with('error', 'An error occurred');
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\LeaveType
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_leave_type= LeaveType::find($id);
        if($master_leave_type->save()){
            return response()->json([
                'success' => 'Provinsi berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }

}
