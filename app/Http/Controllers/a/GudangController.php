<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MasterGudang;
use App\Beban;
use App\MasterStock;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class GudangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data['dd_beban'] = Beban::all();
        $data['gudang'] = MasterGudang::all();
        return view('gudang.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'nama_gudang' => 'required',
            'lantai' => 'required'
        ]);
        $gudang = new MasterGudang;
        $gudang->nama = $request->nama_gudang;
        $gudang->lantai = $request->lantai;
        $gudang->kategori = $request->kategori;
        $gudang->beban_id = $request->beban;
        $gudang->keterangan = $request->keterangan;
        $gudang->created_by = Auth::id();
        $gudang->created_at = Carbon::now()->toDateTimeString();
        $simpan = $gudang->save();
        if ($simpan) {
            return response()->json([
                'status' => true,
                'message' => 'Data Gudang/Lantai Berhasil di simpan'
            ],200);
        }
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
    public function edit($id)
    {
        //
        $data = MasterGudang::find($id);
        return $data;
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
            'nama_gudang' => 'required',
            'lantai' => 'required'
        ]);
        $gudang = MasterGudang::find($id);
        $gudang->nama = $request->nama_gudang;
        $gudang->lantai = $request->lantai;
        $gudang->kategori = $request->kategori;
        $gudang->beban_id = $request->beban;
        $gudang->keterangan = $request->keterangan;
        $gudang->updated_by = Auth::id();
        $gudang->updated_at = Carbon::now()->toDateTimeString();
        $simpan = $gudang->save();
        if ($simpan) {
            return response()->json([
                'status' => true,
                'message' => 'Data Gudang/Lantai Berhasil di Update'
            ],200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $data = MasterGudang::destroy($id);
        if ($data) {
            return response()->json([
                'status' => true,
                'message' => 'data berhasil di hapus'
            ],200);
        }
    }
}
