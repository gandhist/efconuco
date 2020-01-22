<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use \App\Employee;
use App\WorkingSchedule;
use Generator;
use App\Exports\RptOvertimeExport;
use Excel;
use PDF;

class ReportOvertimeController extends Controller
{
    //

    public function overtime()
    {
        $data['dd'] = Employee::all();
        $data['jabatan'] = \App\Jabatan::all();
        $data['divisi'] = \App\Divisi::all();
        $data['beban'] = \App\Beban::all();
        return view('report.overtime.index', $data);
    }

    public function overtimeList(Request $request)
    {
        $nik = $request->input('filter_nik');
        $jabatan = $request->input('filter_jabatan');
        $divisi = $request->input('filter_divisi');
        $beban = $request->input('filter_beban');

        $columns = array(
            0 => 'a.id',
            1 => 'a.nama',
            2 => 'b.nik',
            3 => 'b.nama',
            4 => 'a.id',
            5 => 'a.id',
        );
        
        $totalData = DB::select(" select COUNT(a.id) as total FROM (
            SELECT a.id, a.nik, a.nama, a.date_joining, b.nama AS level, c.nama AS jabatan, d.nama AS divisi, e.nama AS kantor, g.nama AS area_kerja , f.nama AS pembebanan FROM karyawan a
            LEFT JOIN level b ON a.level_id = b.id
            LEFT JOIN master_jabatan c ON a.jabatan_id = c.id
            LEFT JOIN master_divisi d ON a.divisi_id = d.id
            LEFT JOIN master_kantor e ON a.kantor_id = e.id
            LEFT JOIN beban f ON a.beban_id = f.id
            LEFT JOIN master_kantor g ON a.working_area = g.id
            WHERE a.date_resign IS NULL AND a.deleted_at IS NULL) a")[0]->total;
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada request live search
        if (empty($request->input('search.value'))) {
            $dataRenja = DB::select("
            SELECT a.id, a.nik, a.nama, a.date_joining, b.nama AS level, c.nama AS jabatan, d.nama AS divisi, e.nama AS kantor, g.nama AS area_kerja , f.nama AS pembebanan FROM karyawan a
            LEFT JOIN level b ON a.level_id = b.id
            LEFT JOIN master_jabatan c ON a.jabatan_id = c.id
            LEFT JOIN master_divisi d ON a.divisi_id = d.id
            LEFT JOIN master_kantor e ON a.kantor_id = e.id
            LEFT JOIN beban f ON a.beban_id = f.id
            LEFT JOIN master_kantor g ON a.working_area = g.id
            WHERE a.date_resign IS NULL AND a.deleted_at IS NULL
                order by $order $dir
                limit $limit offset $start
            ");
        }
        else {
            $search = $request->input('search.value');
            $dataRenja = DB::select("
            SELECT a.id, a.nik, a.nama, a.date_joining, b.nama AS level, c.nama AS jabatan, d.nama AS divisi, e.nama AS kantor, g.nama AS area_kerja , f.nama AS pembebanan FROM karyawan a
            LEFT JOIN level b ON a.level_id = b.id
            LEFT JOIN master_jabatan c ON a.jabatan_id = c.id
            LEFT JOIN master_divisi d ON a.divisi_id = d.id
            LEFT JOIN master_kantor e ON a.kantor_id = e.id
            LEFT JOIN beban f ON a.beban_id = f.id
            LEFT JOIN master_kantor g ON a.working_area = g.id
            WHERE a.date_resign IS NULL AND a.deleted_at IS NULL
                    and a.nama like '%$search%'
                    or a.nik like '%$search%'
                order by $order $dir
                limit $limit offset $start
            ");

            $totalFiltered = DB::select("
            SELECT COUNT(a.id) AS filtered FROM
                (SELECT a.id, a.nik, a.nama, a.date_joining, b.nama AS level, c.nama AS jabatan, d.nama AS divisi, e.nama AS kantor, g.nama AS area_kerja , f.nama AS pembebanan FROM karyawan a
                LEFT JOIN level b ON a.level_id = b.id
                LEFT JOIN master_jabatan c ON a.jabatan_id = c.id
                LEFT JOIN master_divisi d ON a.divisi_id = d.id
                LEFT JOIN master_kantor e ON a.kantor_id = e.id
                LEFT JOIN beban f ON a.beban_id = f.id
                LEFT JOIN master_kantor g ON a.working_area = g.id
                WHERE a.date_resign IS NULL AND a.deleted_at IS NULL) a
                WHERE a.nik LIKE '%$search%'
                or a.nama like '%$search%'
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($divisi) || !empty($jabatan) || !empty($beban) ) {
            $search = $request->input('search.value');
            $nik = (!empty($nik)) ? "and a.id = '$nik'" : '' ;
            $divisi = (!empty($divisi)) ? "and a.divisi_id = '$divisi'" : '' ;
            $jabatan = (!empty($jabatan)) ? "and a.jabatan_id = '$jabatan'" : '' ;
            $beban = (!empty($beban)) ? "and a.beban_id = '$beban'" : '' ;

            $dataRenja = DB::select("
            SELECT a.id, a.nik, a.nama, a.date_joining, b.nama AS level, c.nama AS jabatan, d.nama AS divisi, e.nama AS kantor, g.nama AS area_kerja , f.nama AS pembebanan FROM karyawan a
            LEFT JOIN level b ON a.level_id = b.id
            LEFT JOIN master_jabatan c ON a.jabatan_id = c.id
            LEFT JOIN master_divisi d ON a.divisi_id = d.id
            LEFT JOIN master_kantor e ON a.kantor_id = e.id
            LEFT JOIN beban f ON a.beban_id = f.id
            LEFT JOIN master_kantor g ON a.working_area = g.id
            WHERE a.date_resign IS NULL AND a.deleted_at IS NULL
            $nik $divisi $jabatan $beban
                order by $order $dir
                limit $limit offset $start
            ");


            $totalFiltered = DB::select("
            SELECT COUNT(a.id) AS filtered FROM
            (SELECT a.id, a.nik, a.nama, a.date_joining, b.nama AS level, c.nama AS jabatan, d.nama AS divisi, e.nama AS kantor, g.nama AS area_kerja , f.nama AS pembebanan FROM karyawan a
            LEFT JOIN level b ON a.level_id = b.id
            LEFT JOIN master_jabatan c ON a.jabatan_id = c.id
            LEFT JOIN master_divisi d ON a.divisi_id = d.id
            LEFT JOIN master_kantor e ON a.kantor_id = e.id
            LEFT JOIN beban f ON a.beban_id = f.id
            LEFT JOIN master_kantor g ON a.working_area = g.id
            WHERE a.date_resign IS NULL AND a.deleted_at IS NULL $nik $divisi $jabatan $beban) a")[0]->filtered;

        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataRenja)) {
            foreach ($dataRenja as $ro) {
                $edit = url('report/lembur/details', $ro->id);
                $row['no'] = $no;
                $row['nik'] = $ro->nik;
                $row['nama'] = $ro->nama;
                $row['date_joining'] = $ro->date_joining;
                $row['level'] = $ro->level;
                $row['divisi'] = $ro->divisi;
                $row['jabatan'] = $ro->jabatan;
                $row['kantor'] = $ro->kantor;
                $row['area_kerja'] = $ro->area_kerja;
                $row['pembebanan'] = $ro->pembebanan;
                $row['options'] = "
                <a href='$edit' class='btn btn-success' ><span class='fa fa-eye'></span> Show</button>
                ";
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

    public function overtime_details($id)
    {
        $data['dd'] = Employee::all();
        $data['jabatan'] = \App\Jabatan::all();
        $data['divisi'] = \App\Divisi::all();
        $data['beban'] = \App\Beban::all();
        $data['months'] = iterator_to_array($this->getMonths())[0];
        $data['id'] = $id;
        $data['bio'] = Employee::find($id);
        return view('report.overtime.details', $data);
    }

    public function rptOvertimeList(Request $request, $id)
    {
        $period = $request->input('period');
        $tahun = $request->input('year');
        $start_cutoff = DB::table('master_cutoff')->get(['start_date']);
        $end_cutoff = DB::table('master_cutoff')->get(['end_date']);
        if ($period != 1) {
            $period = $period-1;
            $start_date = $tahun.'-'.$period.'-'.$start_cutoff[0]->start_date;
        }
        else {
            $tahun12 = $tahun-1;
            $period = '12';
            $start_date = $tahun12.'-'.$period.'-'.$start_cutoff[0]->start_date;
        }
        $period = Carbon::parse($start_date);
        $end_date = $tahun.'-'.$period->addMonths(1)->format('m').'-'.$end_cutoff[0]->end_date;

        // setting parameter sql
        DB::statement( DB::raw( "SET @tanggal_awal = '$start_date'"));
        DB::statement( DB::raw( "SET @tanggal_akhir = '$end_date'"));
        // shift 1
        DB::statement( DB::raw( 'SET @firsthour_ds = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 2)'));
        DB::statement( DB::raw( 'SET @firsthour_day_rest = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 3)'));
        DB::statement( DB::raw( 'SET @sechour_day_rest = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 3)'));
        DB::statement( DB::raw( 'SET @sechour_ds = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 4)'));
        
        // shift 2
        DB::statement( DB::raw( 'SET @firsthour_ns = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 4)'));
        DB::statement( DB::raw( 'SET @firsthour_night_rest_23 = " 23:59:59"'));
        DB::statement( DB::raw( 'SET @firsthour_night_rest = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 1)'));
        DB::statement( DB::raw( 'SET @sechour_night_rest = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 1)'));
        DB::statement( DB::raw( 'SET @sechour_ns = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 2)'));
        
        // rest time
        DB::statement( DB::raw( 'SET @os_duasatu = HOUR(TIMEDIFF(@firsthour_ds, @sechour_ns))'));
        DB::statement( DB::raw( 'SET @os_satudua = HOUR(TIMEDIFF(@firsthour_ns, @sechour_ds))'));
        DB::statement( DB::raw( 'SET @rest_satu = HOUR(TIMEDIFF(@sechour_day_rest, @firsthour_day_rest))'));
        DB::statement( DB::raw( 'SET @rest_dua= HOUR(TIMEDIFF(@sechour_night_rest, @firsthour_night_rest))'));

        $columns = array(
            0 => 'a.id',
            1 => 'a.nama',
            2 => 'b.nik',
            3 => 'b.nama',
            4 => 'a.id',
            5 => 'a.id',
        );
        
        $totalData = WorkingSchedule::where('karyawan_id',$id)->whereBetween('date', [$start_date,$end_date])->count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $dataLembur = DB::select("
        SELECT a.karyawan_id, a.tanggal, 
        CASE 
        WHEN DAYNAME(a.tanggal) = 'Monday' THEN 'Senin'
        WHEN DAYNAME(a.tanggal) = 'Tuesday' THEN 'Selasa'
        WHEN DAYNAME(a.tanggal) = 'Wednesday' THEN 'Rabu'
        WHEN DAYNAME(a.tanggal) = 'Thursday' THEN 'Kamis'
        WHEN DAYNAME(a.tanggal) = 'Friday' THEN 'Jumat'
        WHEN DAYNAME(a.tanggal) = 'Saturday' THEN 'Sabtu'
        WHEN DAYNAME(a.tanggal) = 'Sunday' THEN 'Minggu' 
        END AS hari,
        a.check_in, a.check_out, a.mulai, a.selesai, SUM(a.total_jam_lembur) AS total_jam_lembur, ROUND(SUM(a.telat),2) AS telat, SEC_TO_TIME( SUM( TIME_TO_SEC( a.jam_telat ) ) ) AS jam_telat, SUM(a.fee) AS fee FROM
(
        SELECT
        a.karyawan_id,
        a.date AS tanggal,
        b.masuk AS check_in,
        b.pulang AS check_out,
        c.mulai,
        c.selesai,
        c.total_jam_lembur,
        b.telat,
        b.jam_telat,
        CASE 
            WHEN (c.total_jam_lembur - b.telat) < '3.00' AND (c.total_jam_lembur - b.telat) IS NOT NULL THEN ROUND(3.00 * b.fee_lembur)
            ELSE ROUND((c.total_jam_lembur - b.telat)* b.fee_lembur)
        END AS fee
        FROM working_schedule a LEFT JOIN
        (
        SELECT
        karyawan_id, tanggal, masuk, pulang, 
        ADDTIME(schedule_start, late_tolerance) AS late_tolerance,
        CASE 
            WHEN TIME(masuk) > ADDTIME(schedule_start, late_tolerance) THEN ROUND(TIMESTAMPDIFF(SECOND, ADDTIME(schedule_start, late_tolerance), TIME(masuk)) / 3600,2)
            ELSE 'N/A'
        END AS telat,
        CASE 
            WHEN TIME(masuk) > ADDTIME(schedule_start, late_tolerance) THEN TIMEDIFF(TIME(masuk), ADDTIME(schedule_start, late_tolerance))
            ELSE 'N/A'
        END AS jam_telat,
        fee_lembur
        FROM karyawan_absensi WHERE deleted_at IS NULL AND tanggal BETWEEN @tanggal_awal AND @tanggal_akhir
        ) b ON a.karyawan_id = b.karyawan_id AND a.date = b.tanggal
        LEFT JOIN (
        SELECT 
        karyawan_id,
        tanggal,
        mulai,
        selesai,
        DATEDIFF(DATE(selesai), DATE(mulai))  AS hari_by_date,
        CASE 
        -- 0 day
        WHEN DATEDIFF(DATE(selesai), DATE(mulai)) = 0 THEN 
            CASE
            
                -- 00-01 s/d 02
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai <= CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),' 00:00:00'), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 00-01 s/d 02-07	
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai), @sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua) HOUR), selesai) / 3600,2)
                -- 00-01 s/d 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua) HOUR), CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 00-01 s/d 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu) HOUR), selesai) / 3600,2)
                -- 00-01 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest) ) / 3600,2)
                -- 00-01 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu+@rest_satu) HOUR) , selesai) / 3600,2)
                -- 00-01 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu+@rest_satu) HOUR) , CONCAT(DATE(selesai),@sechour_ds) ) / 3600,2)
                -- 00-01 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu+@rest_satu+@os_satudua) HOUR) , selesai) / 3600,2)
                
                
                -- 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai <= CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, selesai) / 3600,2)
                -- 02-07 s/d 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai),@firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 02-07 s/d 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu) HOUR), selesai) / 3600,2)
                -- 02-07 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 02-07 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu+@rest_satu) HOUR), selesai) / 3600,2)
                -- 02-07 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu+@rest_satu) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 02-07 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu+@rest_satu+@os_satudua) HOUR), selesai) / 3600,2)
                
                
                -- 07-08 s/d 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai <= CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ds), selesai) / 3600,2)
                -- 07-08 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ds) , CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 07-08 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu) HOUR) , selesai ) / 3600,2)
                -- 07-08 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu) HOUR), CONCAT(DATE(selesai), @sechour_ds)  ) / 3600,2)
                -- 07-08 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai ) / 3600,2)
                
                    
                -- 08-12 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 08-12 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu) HOUR), selesai) / 3600,2)
                -- 7-12 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + @rest_satu HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 7-12 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
                
                -- 12-13 s/d 13-17 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@sechour_day_rest) , selesai ) / 3600,2)
                -- 12-13 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@sechour_day_rest), CONCAT(DATE(selesai), @sechour_ds)  ) / 3600,2)
                -- 12-13 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua) HOUR), selesai ) / 3600,2)
                
                -- 13-17 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 13-17 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua) HOUR), selesai) / 3600,2)
                        
                -- 17-18 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ns), selesai ) / 3600,2)
                        
                -- 18 – 24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, selesai) / 3600,2)
                
            END

        WHEN DATEDIFF(DATE(selesai), DATE(mulai)) = 1 THEN 
            CASE
            
            -- == 07-08 == --
                -- 07-08 s/d ND 00-01 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 07-08 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai),@firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 07-08 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 07-08 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 07-08 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 07-08 == --
                
            -- == 08-12 == --
                -- 08-12 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 08-12 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai),@firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 08-12 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 08-12 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 08-12 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 08-12 == --
            
            -- == 12-13 == --
                -- 12-13 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua) HOUR), selesai) / 3600,2)
                -- 12-13 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 12-13 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL +  (@os_satudua + @rest_dua) HOUR) , selesai) / 3600,2)		
                -- 12-13 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + ( @os_satudua + @rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 12-13 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 12-13 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 12-13 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 12-13 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 12-13 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 12-13 == --		
                
            -- == 13-17 == --
                -- 13-17 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua) HOUR), selesai) / 3600,2)
                -- 13-17 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 13-17 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL +  (@os_satudua + @rest_dua) HOUR) , selesai) / 3600,2)		
                -- 13-17 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)		
                -- 13-17 s/d ND 08-12 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 13-17 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 13-17 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu) HOUR), selesai) / 3600,2)
                -- 13-17 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 13-17 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 13-17 == --
            
            -- == 17-18 == --
                -- 17-18 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ns), selesai) / 3600,2)
                -- 17-18 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ns), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 17-18 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL +  (@rest_dua) HOUR) , selesai) / 3600,2)		
                -- 17-18 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 17-19 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 17-19 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 17-19 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 17-19 s/d ND 17-18
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 17-19 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 17-18 == --
            
            -- == 18-24 == -- 
                -- 18-24 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, selesai) / 3600,2)
                -- 18-24 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 19-24 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + ( @rest_dua) HOUR), selesai) / 3600,2)		
                -- 19-24 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 19-24 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 19-24 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 19-24 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu + @rest_satu) HOUR), selesai) / 3600,2)
                -- 19-24 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 19-24 s/d 19-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 19-24 == --
            END
        END AS total_jam_lembur
        FROM
        karyawan_lembur
        WHERE DATE(mulai) BETWEEN @tanggal_awal 
        AND @tanggal_akhir 
        AND deleted_at IS NULL
        GROUP BY mulai, karyawan_id
        ) c ON a.karyawan_id = c.karyawan_id AND a.date = c.tanggal
        WHERE a.date BETWEEN @tanggal_awal AND @tanggal_akhir
        AND a.deleted_at IS NULL
        AND a.karyawan_id = $id
        GROUP BY a.date, a.karyawan_id
        ORDER BY a.karyawan_id, a.date ASC) a
        GROUP BY a.tanggal WITH ROLLUP
        ");

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataLembur)) {
            foreach ($dataLembur as $ro) {
                $row['no'] = $no;
                $row['nik'] = Employee::find($id)->nik;
                $row['nama'] = Employee::find($id)->nama;
                $row['tanggal'] = $ro->tanggal;
                $row['hari'] = $ro->hari;
                $row['check_in'] = $ro->check_in;
                $row['check_out'] = $ro->check_out;
                $row['mulai'] = $ro->mulai;
                $row['selesai'] = $ro->selesai;
                $row['total_jam_lembur'] = $ro->total_jam_lembur;
                $row['jam_telat'] = $ro->jam_telat;
                $row['fee'] = "Rp " . number_format($ro->fee,2,',','.');
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

    public function export_overtime_xls($period, $tahun, $id)
    {
        $start_cutoff = DB::table('master_cutoff')->get(['start_date']);
        $end_cutoff = DB::table('master_cutoff')->get(['end_date']);
        if ($period != 1) {
            $period = $period-1;
            $start_date = $tahun.'-'.$period.'-'.$start_cutoff[0]->start_date;
        }
        else {
            $tahun12 = $tahun-1;
            $period = '12';
            $start_date = $tahun12.'-'.$period.'-'.$start_cutoff[0]->start_date;
        }
        $period = Carbon::parse($start_date);
        $end_date = $tahun.'-'.$period->addMonths(1)->format('m').'-'.$end_cutoff[0]->end_date;
        $nama = Employee::find($id)->nama;
        return Excel::download(new RptOvertimeExport($start_date, $end_date, $id), 'overtime_export-'.$id.'-'.$nama.'-'.$period->format('m').'-'.$tahun.'-' . time() . '.xlsx');
    }

    public function export_overtime_pdf($period, $tahun, $id){
        $start_cutoff = DB::table('master_cutoff')->get(['start_date']);
        $end_cutoff = DB::table('master_cutoff')->get(['end_date']);
        if ($period != 1) {
            $period = $period-1;
            $start_date = $tahun.'-'.$period.'-'.$start_cutoff[0]->start_date;
        }
        else {
            $tahun12 = $tahun-1;
            $period = '12';
            $start_date = $tahun12.'-'.$period.'-'.$start_cutoff[0]->start_date;
        }
        $period = Carbon::parse($start_date);
        $end_date = $tahun.'-'.$period->addMonths(1)->format('m').'-'.$end_cutoff[0]->end_date;

        // setting parameter sql
        DB::statement( DB::raw( "SET @tanggal_awal = '$start_date'"));
        DB::statement( DB::raw( "SET @tanggal_akhir = '$end_date'"));
        // shift 1
        DB::statement( DB::raw( 'SET @firsthour_ds = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 2)'));
        DB::statement( DB::raw( 'SET @firsthour_day_rest = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 3)'));
        DB::statement( DB::raw( 'SET @sechour_day_rest = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 3)'));
        DB::statement( DB::raw( 'SET @sechour_ds = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 4)'));
        
        // shift 2
        DB::statement( DB::raw( 'SET @firsthour_ns = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 4)'));
        DB::statement( DB::raw( 'SET @firsthour_night_rest_23 = " 23:59:59"'));
        DB::statement( DB::raw( 'SET @firsthour_night_rest = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 1)'));
        DB::statement( DB::raw( 'SET @sechour_night_rest = (SELECT CONCAT(" ",end_time) FROM master_lembur_rest WHERE id = 1)'));
        DB::statement( DB::raw( 'SET @sechour_ns = (SELECT CONCAT(" ",start_time) FROM master_lembur_rest WHERE id = 2)'));
        
        // rest time
        DB::statement( DB::raw( 'SET @os_duasatu = HOUR(TIMEDIFF(@firsthour_ds, @sechour_ns))'));
        DB::statement( DB::raw( 'SET @os_satudua = HOUR(TIMEDIFF(@firsthour_ns, @sechour_ds))'));
        DB::statement( DB::raw( 'SET @rest_satu = HOUR(TIMEDIFF(@sechour_day_rest, @firsthour_day_rest))'));
        DB::statement( DB::raw( 'SET @rest_dua= HOUR(TIMEDIFF(@sechour_night_rest, @firsthour_night_rest))'));
        //return Employee::query()->select('nama','nik')->get();

        $data['overtime'] = DB::select("
        SELECT b.nik, b.nama, a.tanggal,
        CASE 
	WHEN DAYNAME(a.tanggal) = 'Monday' THEN 'Senin'
	WHEN DAYNAME(a.tanggal) = 'Tuesday' THEN 'Selasa'
	WHEN DAYNAME(a.tanggal) = 'Wednesday' THEN 'Rabu'
	WHEN DAYNAME(a.tanggal) = 'Thursday' THEN 'Kamis'
	WHEN DAYNAME(a.tanggal) = 'Friday' THEN 'Jumat'
	WHEN DAYNAME(a.tanggal) = 'Saturday' THEN 'Sabtu'
	WHEN DAYNAME(a.tanggal) = 'Sunday' THEN 'Minggu' 
    END AS hari, 
        a.check_in, a.check_out, a.mulai, a.selesai, SUM(a.total_jam_lembur) AS total_jam_lembur, ROUND(SUM(a.telat),2) AS telat, SEC_TO_TIME( SUM( TIME_TO_SEC( a.jam_telat ) ) ) AS jam_telat, SUM(a.fee) AS fee FROM
(
        SELECT
        a.karyawan_id,
        a.date AS tanggal,
        b.masuk AS check_in,
        b.pulang AS check_out,
        c.mulai,
        c.selesai,
        c.total_jam_lembur,
        b.telat,
        b.jam_telat,
        CASE 
            WHEN (c.total_jam_lembur - b.telat) < '3.00' AND (c.total_jam_lembur - b.telat) IS NOT NULL THEN ROUND(3.00 * b.fee_lembur)
            ELSE ROUND((c.total_jam_lembur - b.telat)* b.fee_lembur)
        END AS fee
        FROM working_schedule a LEFT JOIN
        (
        SELECT
        karyawan_id, tanggal, masuk, pulang, 
        ADDTIME(schedule_start, late_tolerance) AS late_tolerance,
        CASE 
            WHEN TIME(masuk) > ADDTIME(schedule_start, late_tolerance) THEN ROUND(TIMESTAMPDIFF(SECOND, ADDTIME(schedule_start, late_tolerance), TIME(masuk)) / 3600,2)
            ELSE 'N/A'
        END AS telat,
        CASE 
            WHEN TIME(masuk) > ADDTIME(schedule_start, late_tolerance) THEN TIMEDIFF(TIME(masuk), ADDTIME(schedule_start, late_tolerance))
            ELSE 'N/A'
        END AS jam_telat,
        fee_lembur
        FROM karyawan_absensi WHERE deleted_at IS NULL AND tanggal BETWEEN @tanggal_awal AND @tanggal_akhir
        ) b ON a.karyawan_id = b.karyawan_id AND a.date = b.tanggal
        LEFT JOIN (
        SELECT 
        karyawan_id,
        tanggal,
        mulai,
        selesai,
        DATEDIFF(DATE(selesai), DATE(mulai))  AS hari_by_date,
        CASE 
        -- 0 day
        WHEN DATEDIFF(DATE(selesai), DATE(mulai)) = 0 THEN 
            CASE
            
                -- 00-01 s/d 02
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai <= CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),' 00:00:00'), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 00-01 s/d 02-07	
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai), @sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua) HOUR), selesai) / 3600,2)
                -- 00-01 s/d 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua) HOUR), CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 00-01 s/d 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu) HOUR), selesai) / 3600,2)
                -- 00-01 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest) ) / 3600,2)
                -- 00-01 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu+@rest_satu) HOUR) , selesai) / 3600,2)
                -- 00-01 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu+@rest_satu) HOUR) , CONCAT(DATE(selesai),@sechour_ds) ) / 3600,2)
                -- 00-01 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),' 00:00:00') AND CONCAT(DATE(mulai),@firsthour_night_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua+@os_duasatu+@rest_satu+@os_satudua) HOUR) , selesai) / 3600,2)
                
                
                -- 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai <= CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, selesai) / 3600,2)
                -- 02-07 s/d 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai),@firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 02-07 s/d 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu) HOUR), selesai) / 3600,2)
                -- 02-07 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 02-07 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu+@rest_satu) HOUR), selesai) / 3600,2)
                -- 02-07 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu+@rest_satu) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 02-07 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_night_rest) AND CONCAT(DATE(mulai),@sechour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_duasatu+@rest_satu+@os_satudua) HOUR), selesai) / 3600,2)
                
                
                -- 07-08 s/d 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai <= CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ds), selesai) / 3600,2)
                -- 07-08 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ds) , CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 07-08 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu) HOUR) , selesai ) / 3600,2)
                -- 07-08 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu) HOUR), CONCAT(DATE(selesai), @sechour_ds)  ) / 3600,2)
                -- 07-08 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai ) / 3600,2)
                
                    
                -- 08-12 s/d 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 08-12 s/d 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu) HOUR), selesai) / 3600,2)
                -- 7-12 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + @rest_satu HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 7-12 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
                
                -- 12-13 s/d 13-17 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@sechour_day_rest) , selesai ) / 3600,2)
                -- 12-13 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@sechour_day_rest), CONCAT(DATE(selesai), @sechour_ds)  ) / 3600,2)
                -- 12-13 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua) HOUR), selesai ) / 3600,2)
                
                -- 13-17 s/d 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 13-17 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua) HOUR), selesai) / 3600,2)
                        
                -- 17-18 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ns), selesai ) / 3600,2)
                        
                -- 18 – 24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, selesai) / 3600,2)
                
            END

        WHEN DATEDIFF(DATE(selesai), DATE(mulai)) = 1 THEN 
            CASE
            
            -- == 07-08 == --
                -- 07-08 s/d ND 00-01 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 07-08 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai),@firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 07-08 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 07-08 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 07-08 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 07-08 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ns) AND CONCAT(DATE(mulai),@firsthour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ds), INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 07-08 == --
                
            -- == 08-12 == --
                -- 08-12 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 08-12 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai),@firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua) HOUR), CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 08-12 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai),@firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai),@sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 08-12 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai),@sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 08-12 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai),@firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 08-12 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ds) AND CONCAT(DATE(mulai),@firsthour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai),@firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_satu + @os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 08-12 == --
            
            -- == 12-13 == --
                -- 12-13 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua) HOUR), selesai) / 3600,2)
                -- 12-13 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 12-13 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL +  (@os_satudua + @rest_dua) HOUR) , selesai) / 3600,2)		
                -- 12-13 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + ( @os_satudua + @rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 12-13 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 12-13 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 12-13 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 12-13 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 12-13 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_day_rest) AND CONCAT(DATE(mulai),@sechour_day_rest) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@sechour_day_rest), INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 12-13 == --		
                
            -- == 13-17 == --
                -- 13-17 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua) HOUR), selesai) / 3600,2)
                -- 13-17 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua) HOUR), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 13-17 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL +  (@os_satudua + @rest_dua) HOUR) , selesai) / 3600,2)		
                -- 13-17 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)		
                -- 13-17 s/d ND 08-12 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 13-17 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 13-17 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu) HOUR), selesai) / 3600,2)
                -- 13-17 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 13-17 s/d ND 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_day_rest) AND CONCAT(DATE(mulai),@sechour_ds) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@os_satudua + @rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 13-17 == --
            
            -- == 17-18 == --
                -- 17-18 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ns), selesai) / 3600,2)
                -- 17-18 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, CONCAT(DATE(mulai),@firsthour_ns), CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 17-18 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL +  (@rest_dua) HOUR) , selesai) / 3600,2)		
                -- 17-18 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 17-19 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 17-19 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 17-19 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu + @rest_satu ) HOUR), selesai) / 3600,2)
                -- 17-19 s/d ND 17-18
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 17-19 s/d 18-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@sechour_ds) AND CONCAT(DATE(mulai),@firsthour_ns) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(CONCAT(DATE(mulai),@firsthour_ns), INTERVAL + (@rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 17-18 == --
            
            -- == 18-24 == -- 
                -- 18-24 s/d ND 00-01
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai <= CONCAT(DATE(selesai),@firsthour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, selesai) / 3600,2)
                -- 18-24 s/d ND 01-02 REST 
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_night_rest) AND CONCAT(DATE(selesai),@sechour_night_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, mulai, CONCAT(DATE(selesai),@firsthour_night_rest)) / 3600,2)
                -- 19-24 s/d ND 02-07
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_night_rest) AND CONCAT(DATE(selesai),@sechour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + ( @rest_dua) HOUR), selesai) / 3600,2)		
                -- 19-24 s/d ND 07-08 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ns) AND CONCAT(DATE(selesai), @firsthour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua) HOUR ) , CONCAT(DATE(selesai),@sechour_ns)) / 3600,2)
                -- 19-24 s/d ND 08-12
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ds) AND CONCAT(DATE(selesai), @firsthour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu) HOUR), selesai) / 3600,2)
                -- 19-24 s/d ND 12-13 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_day_rest) AND CONCAT(DATE(selesai), @sechour_day_rest) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu) HOUR), CONCAT(DATE(selesai),@firsthour_day_rest)) / 3600,2)
                -- 19-24 s/d ND 13-17
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_day_rest) AND CONCAT(DATE(selesai), @sechour_ds) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu + @rest_satu) HOUR), selesai) / 3600,2)
                -- 19-24 s/d ND 17-18 REST
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@sechour_ds) AND CONCAT(DATE(selesai), @firsthour_ns) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu + @rest_satu ) HOUR), CONCAT(DATE(selesai),@sechour_ds)) / 3600,2)
                -- 19-24 s/d 19-24
                WHEN mulai BETWEEN CONCAT(DATE(mulai),@firsthour_ns) AND CONCAT(DATE(mulai),@firsthour_night_rest_23) AND selesai BETWEEN CONCAT(DATE(selesai),@firsthour_ns) AND CONCAT(DATE(selesai), @firsthour_night_rest_23) THEN ROUND(TIMESTAMPDIFF(SECOND, DATE_ADD(mulai, INTERVAL + (@rest_dua + @os_duasatu + @rest_satu + @os_satudua) HOUR), selesai) / 3600,2)
            -- == 19-24 == --
            END
        END AS total_jam_lembur
        FROM
        karyawan_lembur
        WHERE DATE(mulai) BETWEEN @tanggal_awal 
        AND @tanggal_akhir 
        AND deleted_at IS NULL
        GROUP BY mulai, karyawan_id
        ) c ON a.karyawan_id = c.karyawan_id AND a.date = c.tanggal
        WHERE a.date BETWEEN @tanggal_awal AND @tanggal_akhir
        AND a.deleted_at IS NULL
        AND a.karyawan_id = $id
        GROUP BY a.date, a.karyawan_id
        ORDER BY a.karyawan_id, a.date ASC) a INNER JOIN karyawan b ON a.karyawan_id = b.id
        GROUP BY a.tanggal WITH ROLLUP
        ");

        $data['title'] = 'Report Overtime';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['nik'] = Employee::find($id)->nik;
        $data['nama'] = Employee::find($id)->nama;
        $nama = Employee::find($id)->nama;
        $pdf = PDF::setPaper('A4', 'landscape')->loadView('Reports.Pdf.RptOvertimeExport', $data);
        //return view('Reports.Pdf.RptOvertimeExport', $data);
        return $pdf->download('overtime_export-'.$id.'-'.$nama.'-'.$period->format('m').'-'.$tahun.'-' . time() . '.pdf');
    }

    protected function getMonths(): Generator {

        foreach (range(1, Carbon::MONTHS_PER_YEAR) as $month) {
            $human = DateTime::createFromFormat('!m', $month)->format('F'); 
            $bulan= str_pad($month, 2, 0, STR_PAD_LEFT);
            $data[] = [
                'angka' => $bulan,
                'nama' => $human
            ];
        }
        yield $data;
    }
}
