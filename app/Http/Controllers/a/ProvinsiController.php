<?php

namespace App\Http\Controllers;

use App\Provinsi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class ProvinsiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_provinsi"] = Provinsi::all();

        return view('provinsi/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $dd_provinsi= Provinsi::all();
        return view('provinsi/create',['dd'=> $dd_provinsi]);
        $master_provinsi->created_by = Auth::id();
        $master_provinsi->created_at = Carbon::now()->toDateTimeString();
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
            'nama' => 'required',
            'nama_singkat' => 'required',
            'ibu_kota_provinsi' => 'required',
        ]);

        if ($error) {
            $master_provinsi = new Provinsi;
            $master_provinsi->nama = $request->nama;
            $master_provinsi->nama_singkat = $request->nama_singkat;
            $master_provinsi->ibu_kota_provinsi = $request->ibu_kota_provinsi;
            $master_provinsi->created_by = Auth::id();
            if($master_provinsi->save())
            return redirect('/provinsi')->with('success', 'Provinsi berhasil ditambahkan');
            else
            return redirect('/provinsi')->with('error', 'An error occurred');};
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Provinsi
     * @return \Illuminate\Http\Response
     */
    public function show(Provinsi $master_provinsi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Provinsi
     * @return \Illuminate\Http\Response
     */
    public function edit(Provinsi $master_provinsi,$id)
    {
        $dd_provinsi= Provinsi::all();
        $data['master_provinsi'] = $master_provinsi::find($id);
        return view('provinsi/edit',['dd'=> $dd_provinsi])->with($data);
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
            'nama' => 'required',
            'nama_singkat' => 'required',
            'ibu_kota_provinsi' => 'required',
        ]);

        if ($error){
        $master_provinsi= Provinsi::find($id);
        $master_provinsi->nama = $request->get('nama');
        $master_provinsi->nama_singkat = $request->get('nama_singkat');
        $master_provinsi->ibu_kota_provinsi = $request->get('ibu_kota_provinsi');
        $master_provinsi->updated_by = Auth::id();

        if($master_provinsi->save())
            return redirect('/provinsi')->with('success', 'Provinsi berhasil diupdate');
        else
            return redirect('/provinsi')->with('error', 'An error occurred');
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provinsi
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_provinsi= Provinsi::find($id);
        $master_provinsi->deleted_by = Auth::id();
        $master_provinsi->deleted_at = Carbon::now()->toDateTimeString();

        if($master_provinsi->save()){
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
