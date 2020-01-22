<?php

namespace App\Http\Controllers;

use App\Kantor;
use App\Lembur;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LemburController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_lembur"] = Lembur::all();
        return view('lembur/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['dd_kantor'] = Kantor::all();
        $data['dd_lembur'] = Lembur::all();
        return view('lembur/create', $data);
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
            'kantor_id' => 'required',
            'name' => 'required',
            'fee' => 'required',
        ]);

        if ($error) {
            $master_lembur = new Lembur();
            $master_lembur->kantor_id = $request->get('kantor_id');
            $master_lembur->name = $request->get('name');
            $master_lembur->weekday_start = $request->get('weekday_start');
            $master_lembur->weekday_end = $request->get('weekday_end');
            $master_lembur->sat_start = $request->get('sat_start');
            $master_lembur->sat_end = $request->get('sat_end');
            $master_lembur->dayoff_start = $request->get('dayoff_start');
            $master_lembur->dayoff_end = $request->get('dayoff_end');
            $master_lembur->fee = $request->get('fee');
            $master_lembur->keterangan = $request->get('keterangan');
            $master_lembur->status = $request->get('status');
            $master_lembur->created_by = Auth::id();
            if ($master_lembur->save())
                return redirect('/lembur')->with('success', 'Lembur berhasil ditambahkan');
            else
                return redirect('/lembur')->with('error', 'An error occurred');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Lembur
     * @return \Illuminate\Http\Response
     */
    public function show(Lembur $master_lembur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Lembur
     * @return \Illuminate\Http\Response
     */
    public function edit(Lembur $master_lembur, $id)
    {
        $dd_lembur = Kantor::all();
        $data['master_lembur'] = $master_lembur::find($id);
        return view('lembur/edit', ['dd' => $dd_lembur])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Lembur
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'kantor_id' => 'required',
            'name' => 'required',
            'fee' => 'required|regex:/^[0-9]+$/'
        ]);
        $master_lembur = Lembur::find($id);
        $master_lembur->kantor_id = $request->get('kantor_id');
        $master_lembur->name = $request->get('name');
        $master_lembur->weekday_start = $request->get('weekday_start');
        $master_lembur->weekday_end = $request->get('weekday_end');
        $master_lembur->sat_start = $request->get('sat_start');
        $master_lembur->sat_end = $request->get('sat_end');
        $master_lembur->dayoff_start = $request->get('dayoff_start');
        $master_lembur->dayoff_end = $request->get('dayoff_end');
        $master_lembur->fee = $request->get('fee');
        $master_lembur->keterangan = $request->get('keterangan');
        $master_lembur->status = $request->get('status');
        $master_lembur->updated_by = Auth::id();

        if ($master_lembur->save())
            return redirect('/lembur')->with('success', 'Lembur berhasil diupdate');
        else
            return redirect('/lembur')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Lembur
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_lembur = Lembur::find($id);
        $master_lembur->deleted_by = Auth::id();
        $master_lembur->deleted_at = Carbon::now()->toDateTimeString();

        if ($master_lembur->save()) {
            return response()->json([
                'success' => 'Lembur berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }

    public function chained_provinsi_kota(Request $request)
    {

        if ($request->provinsi) {
            return $data = DB::table('master_kota')
                ->where('provinsi_id', '=', $request->provinsi)
                ->get(['id', 'nama as text']);
        } else {
            return $data = DB::table('master_kota')
                ->where('id', '=', $request->kota)
                ->get(['provinsi_id']);
        }
    }
}
