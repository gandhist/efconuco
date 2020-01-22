<?php

namespace App\Http\Controllers;
use App\Beban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BebanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data["beban"] = Beban::all();
        return view('beban/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        
        return view('beban/create');

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
        $request->validate([
            'nama'=>'required',
            'kode_beban'=>'required'
        ]);
        
        $beban = new Beban();
        $beban->nama=$request->get('nama');
        $beban->kode_beban=$request->get('kode_beban');
        $beban->created_by = Auth::id();
        $beban->created_at = Carbon::now()->toDateTimeString();
        $beban->save();
        return redirect('/beban')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(beban $data_beban,$id)
    {
        //
        $data["beban"] = $data_beban::find($id);
        return view('beban/edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate([
            'nama'=>'required'
        ]);
        $data_beban = Beban::find($id);
        $data_beban->nama =  $request->get('nama');
        $data_beban->kode_beban =  $request->get('kode_beban');
        $data_beban->updated_by = Auth::id();
        $data_beban->updated_at = Carbon::now()->toDateTimeString();
        $data_beban->save();
        return redirect('/beban')->with('success', 'Beban berhasil diupdate');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data_beban= Beban::find($id);
        $data_beban->deleted_by = Auth::id();
        $data_beban->deleted_at = Carbon::now()->toDateTimeString();

        if($data_beban->save()){
            return response()->json([
                'success' => 'Beban berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
