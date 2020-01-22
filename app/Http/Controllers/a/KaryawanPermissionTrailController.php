<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\KaryawanPermissionTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class KaryawanPermissionTrailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {

            $filter_karyawan_id = $request->filter_karyawan_id;
            $filter_permission_date = $request->filter_permission_date;
            $filter_permission_id = $request->filter_permission_id;

            if ($filter_karyawan_id && $filter_permission_date && $filter_permission_id) {
                $data = DB::table('karyawan_permission_trail AS kpt')
                    ->select('ky.nama', 'kpt.*')
                    ->where('kpt.karyawan_id', $filter_karyawan_id)
                    ->where('kpt.permission_date', $filter_permission_date)
                    ->where('kpt.permission_id', $filter_permission_id)
                    ->join('karyawan AS ky', 'kpt.karyawan_id', '=', 'ky.id')
                    ->get();
            } else if ($filter_karyawan_id && $filter_permission_date && !$filter_permission_id) {
                $data = DB::table('karyawan_permission_trail AS kpt')
                    ->select('ky.nama', 'kpt.*')
                    ->where('kpt.karyawan_id', $filter_karyawan_id)
                    ->where('kpt.permission_date', $filter_permission_date)
                    ->join('karyawan AS ky', 'kpt.karyawan_id', '=', 'ky.id')
                    ->get();
            } else if ($filter_karyawan_id && !$filter_permission_date && $filter_permission_id) {
                $data = DB::table('karyawan_permission_trail AS kpt')
                    ->select('ky.nama', 'kpt.*')
                    ->where('kpt.karyawan_id', $filter_karyawan_id)
                    ->where('kpt.permission_id', $filter_permission_id)
                    ->join('karyawan AS ky', 'kpt.karyawan_id', '=', 'ky.id')
                    ->get();
            } else if ($filter_karyawan_id && !$filter_permission_date && !$filter_permission_id) {
                $data = DB::table('karyawan_permission_trail AS kpt')
                    ->select('ky.nama', 'kpt.*')
                    ->where('kpt.karyawan_id', $filter_karyawan_id)
                    ->join('karyawan AS ky', 'kpt.karyawan_id', '=', 'ky.id')
                    ->get();
            } else {
                $data = DB::table('karyawan_permission_trail AS kpt')
                    ->select('ky.nama', 'kpt.*')
                    ->orderBy('kpt.permission_id', 'asc')
                    ->join('karyawan AS ky', 'kpt.karyawan_id', '=', 'ky.id')
                    ->get();
            }

            return datatables()->of($data)
                // ->setRowClass('{{ $id % 1 == 0 ? "alert-success" : "alert-warning" }}')
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $loc = "'" . route('karyawanpermissiontrail.edit', $data->id) . "'";
                    return '<a class="btn btn-warning btn-xs" onclick="goshow(' . $loc . ')"><span class="glyphicon glyphicon-pencil"></span>Edit</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $karyawan = [];
        $kirims = DB::table('karyawan_permission_trail AS kape')
            ->select('kyw.nama', 'kape.karyawan_id')
            ->join('karyawan AS kyw', 'kape.karyawan_id', '=', 'kyw.id')
            ->distinct()
            ->get();

        foreach ($kirims as $kirim) {
            $karyawan[] = $kirim;
        }
        return view('karyawanpermissiontrail.index', compact('karyawan'));
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
        $data = DB::table('karyawan_permission_trail AS trail')
            ->select('karyawan.nama', 'trail.*')
            ->where('trail.id', $id)
            ->join('karyawan AS karyawan', 'trail.karyawan_id', '=', 'karyawan.id')
            ->get();
        return response()->json(['data' => $data]);
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
        $rules = [
            'keterangan' => 'required',
            'status' => 'required',
        ];

        $error = Validator::make($request->all(), $rules);

        if ($error->fails()) {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $form_data = [
            'permission_id' => $request->permission_id,
            'karyawan_id' => $request->karyawan_id,
            'permission_date' => $request->permission_date,
            'keterangan' => $request->keterangan,
            'is_fullday' => $request->is_fullday,
            'status' => $request->status,
            'updated_by' => Auth::id()
        ];

        KaryawanPermissionTrail::whereId($id)->update($form_data);

        //Insert Data to karyawan_permission_log
        $permission_log_ins = KaryawanPermissionTrail::whereId($id)->first();
        // dd($permission_log_ins);
        DB::table('karyawan_permission_log')->insert([
            'permission_trail_id' => $permission_log_ins->id,
            'keterangan' => $permission_log_ins->keterangan,
            'status' => $permission_log_ins->status
        ]);

        return response()->json(['success' => 'Data Karyawan Ijin berhasil dirubah!']);
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
    }
}
