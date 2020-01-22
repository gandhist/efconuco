<?php

namespace App\Http\Controllers;

use App\KuotaCuti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KuotaCutiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kuotacuti = DB::table('karyawan_leave_quota AS klq')
            ->join('karyawan', 'klq.karyawan_id', '=', 'karyawan.id')
            ->where('klq.is_taken', '0')
            ->select(DB::raw('count(klq.is_taken) as j, karyawan.nama, klq.id'))
            ->groupBy('klq.karyawan_id')
            ->get();
        return view('kuotacuti.index', ['kuotacuti' => $kuotacuti]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $karyawan = DB::table('karyawan')->get();
        $tipeCuti = DB::table('master_leave_type as mlt')->get();
        return view('kuotacuti/create', ['karyawan' => $karyawan, 'tipeCuti' => $tipeCuti]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $jumlahBaris = $request->jumlah_baris;
        for ($i = 1; $i <= $jumlahBaris; $i++) {
            DB::table('karyawan_leave_quota')->insert([
                'karyawan_id' => $request->karyawan_id,
                'is_taken' => 0,
                'keterangan' => $request->keterangan,
                'leave_type_id' => $request->leave_type_id,
                'leave_date' => NULL
            ]);
        }
        return redirect('/kuotacuti')->with('status', 'Data Kuota Cuti Berhasil Ditambahkan!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\KuotaCuti  $kuotaCuti
     * @return \Illuminate\Http\Response
     */
    public function show(KuotaCuti $kuotaCuti)
    {
        $namaQuota = DB::table('karyawan_leave_quota as klq')
            ->join('karyawan', 'klq.karyawan_id', '=', 'karyawan.id')
            ->where('klq.karyawan_id', $kuotaCuti->karyawan_id)
            ->where(function ($query) {
                $query->where('klq.is_taken', 0);
            })
            ->select(DB::raw('count(klq.is_taken) as j, karyawan.nama as kn'))
            ->get();

        $leaveType = DB::table('karyawan_leave_quota as ltq')
            ->join('master_leave_type as mlt', 'ltq.leave_type_id', '=', 'mlt.id')
            ->where('ltq.karyawan_id', $kuotaCuti->karyawan_id)
            ->select(DB::raw('ltq.leave_date, ltq.keterangan, mlt.nama_cuti, ltq.is_taken, ltq.id'))
            ->get();
        return view('kuotacuti/show', ['namaQuota' => $namaQuota, 'leaveType' => $leaveType]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\KuotaCuti  $kuotaCuti
     * @return \Illuminate\Http\Response
     */
    public function edit(KuotaCuti $kuotaCuti)
    {
        $karyawan = DB::table('karyawan')->get();
        $tipeCuti = DB::table('master_leave_type as mlt')->get();
        return view('kuotacuti.edit', ['karyawan' => $karyawan, 'kuotaCuti' => $kuotaCuti, 'tipeCuti' => $tipeCuti]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\KuotaCuti  $kuotaCuti
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, KuotaCuti $kuotaCuti)
    {
        KuotaCuti::where('id', $kuotaCuti->id)
            ->update([
                'is_taken' => $request->is_taken,
                'keterangan' => $request->keterangan,
                'leave_type_id' => $request->leave_type_id,
                'leave_date' => $request->leave_date
            ]);
        return redirect()->route('kuotaCutiPatch', $kuotaCuti->id);
    }

    /**
     * Catatan
     * Remove the specified resource from storage.
     *
     * @param  \App\KuotaCuti  $kuotaCuti
     * @return \Illuminate\Http\Response
     */
    public function destroy(KuotaCuti $kuotaCuti)
    {
        KuotaCuti::destroy($kuotaCuti->id);
        return redirect('/kuotacuti')->with('status', 'Data Kuota Cuti Berhasil Dihapus!');
    }

    public function del(Request $request)
    {
        $delid = $request->input('delid');
        KuotaCuti::whereIn('id', $delid)->delete();
        return redirect('/kuotacuti')->with('status', 'Data Kuota Cuti Terpilih Berhasil Dihapus!');
    }
}
