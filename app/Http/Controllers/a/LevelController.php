<?php

namespace App\Http\Controllers;

use App\Level;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["level"] = Level::all();

        return view('level/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('level/create');
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
            'keterangan' => 'required',
        ]);
        $level = new Level();
        $level->nama       = $request->get('nama');
        $level->keterangan = $request->get('keterangan');
        $level->status     = $request->get('status');
        $level->created_by = Auth::id();
        if($level->save())
            return redirect('/level')->with('success', 'Level berhasil ditambahkan');
        else
            return redirect('/level')->with('error', 'An error occurred');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Level
     * @return \Illuminate\Http\Response
     */
    public function show(Level $level)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Level
     * @return \Illuminate\Http\Response
     */
    public function edit(Level $level,$id)
    {
        $data['level'] = $level::find($id);
        return view('level/edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Level
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'nama' => 'required',
            'keterangan' => 'required',
        ]);
        
        $level= Level::find($id);
        $level->nama       = $request->get('nama');
        $level->keterangan = $request->get('keterangan');
        $level->status     = $request->get('status');
        $level->updated_by = Auth::id();

        if($level->save())
            return redirect('/level')->with('success', 'Level berhasil diupdate');
        else
            return redirect('/level')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Level
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $level= Level::find($id);
        $level->status     = -1;
        $level->deleted_by = Auth::id();
        $level->deleted_at = Carbon::now()->toDateTimeString();

        if($level->save()){
            return response()->json([
                'success' => 'Level berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
