<?php

namespace App\Http\Controllers;

use App\Jabatan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JabatanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_jabatan"] = Jabatan::where('status', '>=', 0)->get();
        return view('jabatan/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $jabatan = Jabatan::all();
        $jabatan->created_by = Auth::id();
        $jabatan->created_at = Carbon::now()->toDateTimeString();

        $data["jabatan"] = $jabatan;

        return view('jabatan/create')->with($data);
        // return view('jabatan/create',['dd'=> $dd_jabatan]);
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
            'parent' => 'required',
            'nama' => 'required',
        ]);

        if ($error) {
        $jabatan = new Jabatan();
        $jabatan->parent     = $request->get('parent');
        $jabatan->nama       = $request->get('nama');
        $jabatan->keterangan = $request->get('keterangan');
        $jabatan->status     = $request->get('status');
        $jabatan->created_by = Auth::id();

        if($jabatan->save())
            return redirect('/jabatan')->with('success', 'Jabatan berhasil ditambahkan');
        else
            return redirect('/jabatan')->with('error', 'An error occurred');
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Jabatan  $jabatan
     * @return \Illuminate\Http\Response
     */
    public function show(Jabatan $jabatan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Jabatan  $jabatan
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['dd_jabatan'] = Jabatan::all();
        $data['jabatan'] = Jabatan::find($id);
        return view('jabatan.edit',$data);
        // return view('jabatan/edit',['dd'=> $dd_jabatan])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Jabatan  $jabatan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $error = $request->validate([
            'parent' => 'required',
            'nama' => 'required',
        ]);
        $jabatan=Jabatan::find($id);
        $jabatan->parent     = $request->get('parent');
        $jabatan->nama       = $request->get('nama');
        $jabatan->keterangan = $request->get('keterangan');
        $jabatan->status     = $request->get('status');
        $jabatan->updated_by = Auth::id();

        if($jabatan->save())
            return redirect('/jabatan')->with('success', 'Jabatan berhasil diupdate');
        else
            return redirect('/jabatan')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jabatan  $jabatan
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $jabatan= Jabatan::find($id);
        $jabatan->status     = -1;
        $jabatan->deleted_by = Auth::id();
        $jabatan->deleted_at = Carbon::now()->toDateTimeString();

        if($jabatan->save()){
            return response()->json([
                'success' => 'Jabatan berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
