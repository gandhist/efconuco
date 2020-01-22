<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use \App\WorkingSchedule;
use \App\Karyawan;
use \App\KaryawanAbsensi;
use App\Exports\RptKehadiran;
use Excel;
use PDF;

class RptKehadiranController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    
    public function exportRptKehadiran(Request $request){
        // return $request->bulanhidden;
        $bulan = $request->input('bulanhidden');
        $tahun = $request->input('tahunhidden');
        $start_cutoff = DB::table('master_cutoff')->get(['start_date']);
        $end_cutoff = DB::table('master_cutoff')->get(['end_date']);
        if ($bulan != 1) {
            $bulan = $bulan-1;
            $start_date = $tahun.'-'.$bulan.'-'.$start_cutoff[0]->start_date;
        }
        else {
            $tahun12 = $tahun-1;
            $bulan = '12';
            $start_date = $tahun12.'-'.$bulan.'-'.$start_cutoff[0]->start_date;
        }
        $bulan = Carbon::parse($start_date);
        $end_date = $tahun.'-'.$bulan->addMonths(1)->format('m').'-'.$end_cutoff[0]->end_date;
        // return $start_date.$end_date;
        return Excel::download(new RptKehadiran($start_date, $end_date),'Report RptKehadiran_export'.$bulan->format('m').'-'.$tahun.'-'  . '.xlsx');
        //return Employee::query()->select('nama','nik')->get();
    }
    public function exportRptKehadiranPDF(Request $request){
        $bulan = $request->input('bulanpdf');
        $tahun = $request->input('tahunpdf');
        $start_cutoff = DB::table('master_cutoff')->get(['start_date']);
        $end_cutoff = DB::table('master_cutoff')->get(['end_date']);
        if ($bulan != 1) {
            $bulan = $bulan-1;
            $start_date = $tahun.'-'.$bulan.'-'.$start_cutoff[0]->start_date;
        }
        else {
            $tahun12 = $tahun-1;
            $bulan = '12';
            $start_date = $tahun12.'-'.$bulan.'-'.$start_cutoff[0]->start_date;
        }
        $bulan = Carbon::parse($start_date);
        $end_date = $tahun.'-'.$bulan->addMonths(1)->format('m').'-'.$end_cutoff[0]->end_date;

        DB::statement(DB::raw("SET @start_date = '$start_date'"));
        DB::statement(DB::raw("SET @end_date = '$end_date'"));
        $data['laporan'] = DB::select("
        SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, q.masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
        SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, a.cuti, b.izin,skt.sakit,
        DATEDIFF(@end_date, @start_date)-q.masuk - a.cuti - b.izin - skt.sakit AS alpha, w.total_cuti, w.sisa_cuti
        FROM karyawan_absensi ka
        LEFT JOIN
        (SELECT k.id,k.nik,k.nama FROM karyawan k WHERE STATUS = 1 AND deleted_at IS NULL GROUP BY id) kr
        ON ka.karyawan_id = kr.id
        LEFT JOIN
        (SELECT karyawan_id, COUNT(leave_date) AS cuti FROM karyawan_leave_trail WHERE STATUS = 0 AND leave_date BETWEEN @start_date AND @end_date AND deleted_at IS NULL GROUP BY karyawan_id) a
        ON ka.karyawan_id = a.karyawan_id
        LEFT JOIN
        (SELECT karyawan_id, COUNT(permission_date) AS izin FROM karyawan_permission_trail WHERE STATUS = 0 AND permission_date BETWEEN @start_date AND @end_date GROUP BY karyawan_id) b
        ON ka.karyawan_id = b.karyawan_id
        LEFT JOIN
        (SELECT karyawan_id, COUNT(DATE) AS sakit FROM karyawan_sakit_trail WHERE STATUS = 0 AND DATE BETWEEN @start_date AND @end_date GROUP BY karyawan_id) skt
        ON ka.karyawan_id  = skt.karyawan_id
        LEFT JOIN
        (SELECT ka.karyawan_id, q.nama,COUNT(ka.tanggal) AS masuk 
        FROM karyawan q INNER JOIN karyawan_absensi ka ON q.id = ka.karyawan_id WHERE ka.deleted_at IS NULL 
        AND ka.tanggal BETWEEN @start_date AND @end_date GROUP BY q.nama) q
        ON ka.karyawan_id = q.karyawan_id 
        LEFT JOIN
        (SELECT ka.karyawan_id, TIMEDIFF(pulang,masuk) AS selisih,tanggal,
        CASE
        WHEN TIME(masuk) >= ADDTIME(schedule_start, late_tolerance) THEN TIMEDIFF(TIME(masuk), ADDTIME(schedule_start,late_tolerance))
        ELSE 'N/A'
        END AS telat
        FROM karyawan_absensi ka WHERE deleted_at IS NULL AND tanggal BETWEEN @start_date AND @end_date GROUP BY karyawan_id,tanggal) t1
        ON ka.karyawan_id = t1.karyawan_id AND ka.tanggal = t1.tanggal
        LEFT JOIN
        (
        SELECT p.karyawan_id, p.total_cuti, v.sisa_cuti FROM 
        (SELECT karyawan_id,
        CASE 
        WHEN is_taken = 1 AND leave_date BETWEEN @start_date AND @end_date THEN COUNT(leave_date)
        END AS total_cuti
        FROM karyawan_leave_quota GROUP BY karyawan_id) p
        LEFT JOIN
        (SELECT karyawan_id,
        COUNT(is_taken) AS sisa_cuti
        FROM karyawan_leave_quota WHERE is_taken = 0 GROUP BY karyawan_id)v
        ON p.karyawan_id = v.karyawan_id
        ) w
        ON ka.karyawan_id = w.karyawan_id
        WHERE ka.tanggal BETWEEN @start_date AND @end_date AND ka.deleted_at IS NULL
        GROUP BY ka.karyawan_id
        "); 
        $data['title'] = 'Report Jadwal Rekap Kehadiran';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $pdf = PDF::setPaper('A4', 'landscape')->loadView('Reports.Pdf.RptPdfRekapKehadiran', $data); 
        // return $start_date.$end_date;
         return $pdf->download('RptPdfKehadiran_export'.': '.$bulan->format('m').'-'.$tahun.'-' . '.pdf');
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
        //
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
