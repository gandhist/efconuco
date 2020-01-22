<?php

namespace App\Http\Controllers;

use App\Libur;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LiburController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_libur"] = Libur::all();

        return view('libur/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $dd_libur= Libur::all();
        return view('libur/create');
        //$master_libur->created_by = Auth::id();
        //$master_libur->created_at = Carbon::now()->toDateTimeString();
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
            'tanggal' => 'required',
            'nama' => 'required',
            'keterangan' => 'required',
        ]);
        $master_libur = new Libur();
        $master_libur->tanggal    = $request->get('tanggal');
        $master_libur->nama       = $request->get('nama');
        $master_libur->keterangan = $request->get('keterangan');
        $master_libur->status     = $request->get('status');
        $master_libur->created_by = Auth::id();
        $save = $master_libur->save();
        if($save) {
            return redirect('/libur')->with('success', 'Libur berhasil ditambahkan');
        }
        else {
            return redirect('/libur')->with('error', 'An error occurred');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Libur
     * @return \Illuminate\Http\Response
     */
    public function show(Libur $leve)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Libur
     * @return \Illuminate\Http\Response
     */
    public function edit(Libur $master_libur,$id)
    {
        $data['master_libur'] = $master_libur::find($id);
        return view('libur/edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Libur
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'tanggal' => 'required',
            'nama' => 'required',
            'keterangan' => 'required',
        ]);
        $master_libur= Libur::find($id);
        $master_libur->tanggal    = $request->get('tanggal');
        $master_libur->nama       = $request->get('nama');
        $master_libur->keterangan = $request->get('keterangan');
        $master_libur->status     = $request->get('status');
        $master_libur->updated_by = Auth::id();

        if($master_libur->save())
            return redirect('/libur')->with('success', 'Libur berhasil diupdate');
        else
            return redirect('/libur')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Libur
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_libur= Libur::find($id);
        $master_libur->status     = -1;
        $master_libur->deleted_by = Auth::id();
        $master_libur->deleted_at = Carbon::now()->toDateTimeString();

        if($master_libur->save()){
            return response()->json([
                'success' => 'Libur berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
