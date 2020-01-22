<?php

namespace App\Http\Controllers;

use App\Kantor;
use App\Provinsi;
use App\Kota;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KantorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_kantor"] = Kantor::all();
        return view('kantor/index')->with($data);
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['master_kantor'] = Kantor::all();
        $data['dd_kantor'] = Kantor::all();
        $data['dd_provinsi'] = Provinsi::all();
        $data['dd_kota'] = Kota::all();
        return view('kantor/create',$data);
        $master_kantor->created_by = Auth::id();
        $master_kantor->created_at = Carbon::now()->toDateTimeString();
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
            'provinsi_id' => 'required',
            'kota_id' => 'required',
            'code' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
        ]);

        if ($error) {
        $master_kantor= new Kantor();
        $master_kantor->parent= $request->parent;
        $master_kantor->provinsi_id= $request->provinsi_id;
        $master_kantor->kota_id= $request->kota_id;
        $master_kantor->code= $request->code;
        $master_kantor->nama= $request->nama;
        $master_kantor->alamat= $request->alamat;
        $master_kantor->keterangan= $request->keterangan;
        $master_kantor->status= $request->status;
        $master_kantor->created_by = Auth::id();
        if($master_kantor->save())
            return redirect('/kantor')->with('success', 'Kantor berhasil ditambahkan');
        else
            return redirect('/kantor')->with('error', 'An error occurred');
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Kantor
     * @return \Illuminate\Http\Response
     */
    public function show(Kantor $master_kantor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Kantor
     * @return \Illuminate\Http\Response
     */
    public function edit(Kantor $master_kantor,$id)
    {
        $data['master_kantor'] = Kantor::find($id);
        $data['dd_kantor'] = Kantor::all();
        $data['dd_provinsi'] = Provinsi::all();
        $data['dd_kota'] = Kota::all();
        return view('kantor.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Kantor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
                 //
    $error = $request->validate([
        'parent' => 'required',
        'provinsi_id' => 'required',
        'kota_id' => 'required',
        'code' => 'required',
        'nama' => 'required',
        'alamat' => 'required',
        ]);
        
        if ($error) {
        $master_kantor= Kantor::find($id);
        $master_kantor->parent = $request->get('parent');
        $master_kantor->provinsi_id = $request->get('provinsi_id');
        $master_kantor->kota_id = $request->get('kota_id');
        $master_kantor->code = $request->get('code');
        $master_kantor->nama = $request->get('nama');
        $master_kantor->alamat = $request->get('alamat');
        $master_kantor->keterangan = $request->get('keterangan');
        $master_kantor->status     = $request->get('status');
        $master_kantor->updated_by = Auth::id();

        if($master_kantor->save())
            return redirect('/kantor')->with('success', 'Kantor berhasil diupdate');
        else
            return redirect('/kantor')->with('error', 'An error occurred');
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Kantor
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_kantor= Kantor::find($id);
        $master_kantor->deleted_by = Auth::id();
        $master_kantor->deleted_at = Carbon::now()->toDateTimeString();

        if($master_kantor->save()){
            return response()->json([
                'success' => 'Kantor berhasil dihapus'
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
