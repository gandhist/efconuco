<?php

namespace App\Http\Controllers;

use App\Kota;
use App\Provinsi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KotaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $data['master_kota']= Kota::all();
        // $data['master_kota']= DB::select('select id, get_provinsi_name(provinsi_id) AS provinsi_name, nama FROM master_kota');
        $data['master_kota']= Kota::all();
        return view('kota/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $dd_provinsi= Provinsi::all();
        return view('kota/create',['dd'=> $dd_provinsi]);
        // return view('Kota/create');
        $master_kota->created_by = Auth::id();
        $master_kota->created_at = Carbon::now()->toDateTimeString();
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
            'provinsi_id' => 'required',
            'nama' => 'required',
        ]);

        if ($error) {
        $master_kota = new Kota();
        $master_kota->provinsi_id       = $request->get('provinsi_id');
        $master_kota->nama = $request->get('nama');
        if($master_kota->save())
            return redirect('/kota')->with('success', 'Kota berhasil ditambahkan');
        else
            return redirect('/kota')->with('error', 'An error occurred');
    }

    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Kota
     * @return \Illuminate\Http\Response
     */
    public function show(Kota $master_kota)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Kota
     * @return \Illuminate\Http\Response
     */
    public function edit(Kota $master_kota,$id)
    {
        $dd_kota= Provinsi::all();
        $data['master_kota'] = $master_kota::find($id);
        
        return view('kota/edit',['dd'=> $dd_kota])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Kota
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
         //
         $error = $request->validate([
        'provinsi_id' => 'required',
        'nama' => 'required',]);
        
        $master_kota= Kota::find($id);
        $master_kota->provinsi_id = $request->get('provinsi_id');
        $master_kota->nama = $request->get('nama');

        if($master_kota->save())
            return redirect('/kota')->with('success', 'Kota berhasil diupdate');
        else
            return redirect('/kota')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Kota
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_kota= Kota::find($id);
        //$master_kota->delete();

        if($master_kota->delete()){
            return response()->json([
                'success' => 'Kota berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
