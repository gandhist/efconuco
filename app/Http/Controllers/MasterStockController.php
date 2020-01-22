<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MasterStock;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MasterStockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data['stock'] = MasterStock::all();
        return view('master_stock.index', $data);
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
            'kode_barang' => 'required',
            'nama' => 'required'
        ]);
        $stock = new MasterStock;
        $stock->kode_barang = $request->kode_barang;
        $stock->nama = $request->nama;
        $stock->qty = $request->qty;
        $stock->qty_satuan = $request->satuan;
        $stock->harga = $request->harga;
        $stock->kategori = $request->kategori;
        $stock->created_by = Auth::id();
        $stock->created_at = Carbon::now()->toDateTimeString();
        $simpan = $stock->save();
        if ($simpan) {
            return response()->json([
                'status' => true,
                'message' => 'Data Stock Berhasil di simpan'
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
        $data = MasterStock::find($id);
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
            'kode_barang' => 'required',
            'nama' => 'required',
            'qty' => 'required'
        ]);
        $stock = MasterStock::find($id);
        $stock->kode_barang = $request->kode_barang;
        $stock->nama = $request->nama;
        $stock->qty = $request->qty;
        $stock->qty_satuan = $request->satuan;
        $stock->harga = $request->harga;
        $stock->kategori = $request->kategori;
        $stock->updated_by = Auth::id();
        $stock->updated_at = Carbon::now()->toDateTimeString();
        $simpan = $stock->save();
        if ($simpan) {
            return response()->json([
                'status' => true,
                'message' => 'Data Stock Berhasil di perbarui'
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
        $data = MasterStock::destroy($id);
        if ($data) {
            return response()->json([
                'status' => true,
                'message' => 'data berhasil di hapus'
            ],200);
        }
    }
}
