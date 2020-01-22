<?php

namespace App\Http\Controllers;

use App\WorkingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkingTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $workingdata = DB::table('working_type')
            ->join('master_lembur', 'working_type.lembur_id', '=', 'master_lembur.id')
            ->select('working_type.*', 'master_lembur.name')
            ->get();
        return view('workingtype.index', compact('workingdata'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $lembur = DB::table('master_lembur')->get();
        return view('workingtype/create', compact('lembur'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        WorkingType::create($request->all());
        return redirect('/workingtype')->with('status', 'Data Tipe Kerja Berhasil Ditambahkan!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\WorkingType  $workingType
     * @return \Illuminate\Http\Response
     */
    public function show(WorkingType $workingType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\WorkingType  $workingType
     * @return \Illuminate\Http\Response
     */
    public function edit(WorkingType $workingType)
    {
        // $workingType = WorkingType::all();
        $lembur = DB::table('master_lembur')->get();
        return view('workingtype.edit', ['workingType' => $workingType, 'lembur' => $lembur]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\WorkingType  $workingType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WorkingType $workingType)
    {
        WorkingType::where('id', $workingType->id)
            ->update([
                'nama' => $request->nama,
                'lembur_id' => $request->lembur_id
            ]);
        return redirect('/workingtype')->with('status', 'Data Tipe Kerja Berhasil Diubah!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\WorkingType  $workingType
     * @return \Illuminate\Http\Response
     */
    public function destroy(WorkingType $workingType)
    {
        WorkingType::destroy($workingType->id);
        return redirect('/workingtype')->with('status', 'Data Tipe Kerja Berhasil Dihapus!');
    }
}
