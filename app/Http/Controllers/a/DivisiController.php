<?php

namespace App\Http\Controllers;

use App\Divisi;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DivisiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["divisi"] = Divisi::where('status', '>=', 0)->get();

        return view('divisi/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('divisi/create');
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
        'nama' => 'required',
    ]);
        if ($error) {
        $divisi = new Divisi();
        $divisi->nama       = $request->get('nama');
        $divisi->keterangan = $request->get('keterangan');
        $divisi->status     = $request->get('status');
        $divisi->created_by = Auth::id();

        if($divisi->save())
            return redirect('/divisi')->with('success', 'Divisi berhasil ditambahkan');
        else
            return redirect('/divisi')->with('error', 'An error occurred');
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Divisi  $divisi
     * @return \Illuminate\Http\Response
     */
    public function show(Divisi $divisi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Divisi  $divisi
     * @return \Illuminate\Http\Response
     */
    public function edit(Divisi $divisi)
    {
        $data['divisi'] = $divisi;

        return view('divisi/edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Divisi  $divisi
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Divisi $divisi)
    {
        $error = $request->validate([
            'nama' => 'required',
        ]);
        $divisi->nama       = $request->get('nama');
        $divisi->keterangan = $request->get('keterangan');
        $divisi->status     = $request->get('status');
        $divisi->updated_by = Auth::id();

        if($divisi->save())
            return redirect('/divisi')->with('success', 'Divisi berhasil diupdate');
        else
            return redirect('/divisi')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Divisi  $divisi
     * @return \Illuminate\Http\Response
     */
    public function destroy(Divisi $divisi)
    {
        $divisi->status     = -1;
        $divisi->deleted_by = Auth::id();
        $divisi->deleted_at = Carbon::now()->toDateTimeString();

        if($divisi->save()){
            return response()->json([
                'success' => 'Divisi berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
