<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Employee;
use \App\Sakit;
use \App\KuotaCuti;
use \App\Jabatan;
use \App\Lembur;
use \App\KaryawanAbsensi;
use \App\KaryawanLeave;
use \App\KaryawanLeaveTrail;
use \App\KaryawanLembur;
use \App\KaryawanPermission;
use \App\Permission;
use \App\KaryawanleaveQuota;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RekapKehadiranController extends Controller
{
    //

    public function index(Request $request)
    {
        $dataRekapKehadiran['tglcutoff_awal']= DB::table('master_cutoff')->get(['start_date']);
        $dataRekapKehadiran['tglcutoff_akhir']= DB::table('master_cutoff')->get(['end_date']);
        $bulan = Carbon::parse($request->bulan);
        $bulanakhir = $bulan->addMonths(1)->format('m');   
        $tahun = $request->tahun;
        $start_date = $tahun.'-'.$bulan.'-'.$dataRekapKehadiran['tglcutoff_awal'];
        if($bulan= $request->bulan == '12'){
            $tahun = addyear(1);
        }
        $end_date = $tahun.'-'.$bulanakhir.'-'.$dataRekapKehadiran['tglcutoff_akhir'];
        // return $start_date.$end_date;

        DB::statement(DB::raw("SET @start_date = '$start_date'"));
        DB::statement(DB::raw("SET @end_date = '$end_date'"));
        $dataRekapKehadiran['laporan'] = DB::select("
        SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, COALESCE(q.masuk,0) AS masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
        SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, COALESCE(a.cuti,0) AS cuti, COALESCE(b.izin,0) AS izin,COALESCE(skt.sakit,0) AS sakit,
        (DATEDIFF(@end_date, @start_date)- q.masuk) -COALESCE(a.cuti,0) - COALESCE(b.izin,0) AS alpha, COALESCE(w.total_cuti,0) AS total_cuti, COALESCE(w.sisa_cuti,0) AS sisa_cuti
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
        WHEN is_taken = 1 THEN COUNT(leave_date)
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
        // return $dataRekapKehadiran;
        return view('rekapkehadiran/index',$dataRekapKehadiran);
    }

    public function rekapkehadiranlist(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
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

        //end off format tanggal custom filter
           DB::statement(DB::raw("SET @start_date = '$start_date'"));
           DB::statement(DB::raw("SET @end_date = '$end_date'"));

        $columns = array(
            0 => 'kr.id',
            1 => 'kr.nik',
            2 => 'kr.nama',
            3 => 'total_hari',
            4 => 'q.masuk',
            5 => 'absent',
            6 => 'total_telat',
            7 => 'a.cuti',
            8 => 'b.izin',
            9 => 'skt.sakit',
            10 => 'alpha',
            11 => 'w.total_cuti',
            12 => 'w.sisa_cuti',
        );
        
        $totalData = DB::select(" select COUNT(a.id) AS total FROM
        (SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, COALESCE(q.masuk,0) AS masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
        SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, COALESCE(a.cuti,0) AS cuti, COALESCE(b.izin,0) AS izin,COALESCE(skt.sakit,0) AS sakit,
        (DATEDIFF(@end_date, @start_date)- q.masuk) -COALESCE(a.cuti,0) - COALESCE(b.izin,0) AS alpha, COALESCE(w.total_cuti,0) AS total_cuti, COALESCE(w.sisa_cuti,0) AS sisa_cuti
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
        WHEN is_taken = 1 THEN COUNT(leave_date)
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
        GROUP BY ka.karyawan_id)a")[0]->total;
        // return $start_date.$end_date;

            $totalFiltered = $totalData;

            $limit = $request->length;
            $start = $request->start;
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

        // jika tidak ada request live search
        if (empty($request->input('search.value'))) {
            $dataRekapKehadiran = DB::select(" SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, COALESCE(q.masuk,0) AS masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
            SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, COALESCE(a.cuti,0) AS cuti, COALESCE(b.izin,0) AS izin,COALESCE(skt.sakit,0) AS sakit,
            (DATEDIFF(@end_date, @start_date)- q.masuk) -COALESCE(a.cuti,0) - COALESCE(b.izin,0) AS alpha, COALESCE(w.total_cuti,0) AS total_cuti, COALESCE(w.sisa_cuti,0) AS sisa_cuti
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
            WHEN is_taken = 1 THEN COUNT(leave_date)
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
            GROUP BY ka.karyawan_id order by $order $dir
            limit $limit offset $start
        ");
        }
        else {
            $search = $request->input('search.value');
            $dataRekapKehadiran = DB::select("select kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, q.masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
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
            AND kr.nama LIKE '%$search%'
            OR kr.nik LIKE '%$search%'
            GROUP BY ka.karyawan_id order by $order $dir
            limit $limit offset $start
            ");

            $totalFiltered = DB::select("select COUNT(a.id) AS filtered FROM
            (SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, COALESCE(q.masuk,0) AS masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
            SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, COALESCE(a.cuti,0) AS cuti, COALESCE(b.izin,0) AS izin,COALESCE(skt.sakit,0) AS sakit,
            (DATEDIFF(@end_date, @start_date)- q.masuk) -COALESCE(a.cuti,0) - COALESCE(b.izin,0) AS alpha, COALESCE(w.total_cuti,0) AS total_cuti, COALESCE(w.sisa_cuti,0) AS sisa_cuti
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
            WHEN is_taken = 1 THEN COUNT(leave_date)
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
            AND kr.nik LIKE '%$search%'
            OR kr.nama LIKE '%$search%'
            GROUP BY ka.karyawan_id)a")[0]->filtered;
        }
// custom filter query here
    if (!empty($bulan) || !empty($tahun) ) {
    // $search = $request->input('search.value');
    // $bulan = (!empty($bulan)) ? "and a.id = '$bulan'" : '' ;
    // $tahun = (!empty($tahun)) ? "and a.id = '$tahun'" : '' ;

    $dataRekapKehadiran = DB::select("
    SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, COALESCE(q.masuk,0) AS masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
            SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, COALESCE(a.cuti,0) AS cuti, COALESCE(b.izin,0) AS izin,COALESCE(skt.sakit,0) AS sakit,
            (DATEDIFF(@end_date, @start_date)- q.masuk) -COALESCE(a.cuti,0) - COALESCE(b.izin,0) AS alpha, COALESCE(w.total_cuti,0) AS total_cuti, COALESCE(w.sisa_cuti,0) AS sisa_cuti
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
            WHEN is_taken = 1 THEN COUNT(leave_date)
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
    order by $order $dir
    limit $limit offset $start
    ");

    $totalFiltered = DB::select("select COUNT(a.id) AS filtered FROM
    (SELECT kr.id,kr.nik,kr.nama,DATEDIFF(@end_date, @start_date) AS total_hari, COALESCE(q.masuk,0) AS masuk, DATEDIFF(@end_date, @start_date)-q.masuk AS absent,
    SEC_TO_TIME(SUM(TIME_TO_SEC(t1.telat))) AS total_telat, COALESCE(a.cuti,0) AS cuti, COALESCE(b.izin,0) AS izin,COALESCE(skt.sakit,0) AS sakit,
    (DATEDIFF(@end_date, @start_date)- q.masuk) -COALESCE(a.cuti,0) - COALESCE(b.izin,0) AS alpha, COALESCE(w.total_cuti,0) AS total_cuti, COALESCE(w.sisa_cuti,0) AS sisa_cuti
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
    WHEN is_taken = 1 THEN COUNT(leave_date)
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
    GROUP BY ka.karyawan_id)a")[0]->filtered;

}
    //collection data here
    $data = array();
    $no = 1;
    if (!empty($dataRekapKehadiran)) {
        foreach ($dataRekapKehadiran as $dt) {
            $row['no'] = $no;
            $row['nik'] =$dt->nik;
            $row['nama'] = $dt->nama;
            $row['total_hari'] = $dt->total_hari;
            $row['masuk'] = $dt->masuk;
            $row['absent'] = $dt->absent;
            $row['total_telat'] = $dt->total_telat;
            $row['cuti'] = $dt->cuti;
            $row['izin'] = $dt->izin;
            $row['sakit'] = $dt->sakit;
            $row['alpha'] = $dt->alpha;
            $row['total_cuti'] = $dt->total_cuti;
            $row['sisa_cuti'] = $dt->sisa_cuti;
            $data[] = $row;
            $no++;
        }
    }
    // return data json
    $jsonData = array(
        'draw' => intval($request->input('draw')),
        'recordsTotal' => intval($totalData),
        'recordsFiltered' => intval($totalFiltered),
        'data' => $data,
    );
    echo json_encode($jsonData);
    }
}


