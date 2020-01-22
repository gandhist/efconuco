<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\WorkingSchedule;
use \App\Karyawan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class SecurityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $dataSecurity['tglcutoff_awal'] = DB::table('master_cutoff')->get(['start_date']);
        $dataSecurity['tglcutoff_akhir'] = DB::table('master_cutoff')->get(['end_date']);
        $bulan = Carbon::parse($request->bulan);
        $bulanakhir = $bulan->addMonths(1)->format('m');
        $tahun = $request->tahun;
        $start_date = $tahun . '-' . $bulan . '-' . $dataSecurity['tglcutoff_awal'];
        if ($bulan = $request->bulan == '12') {
            $tahun = addyear(1);
        }
        $end_date = $tahun . '-' . $bulanakhir . '-' . $dataSecurity['tglcutoff_akhir'];
        // return $start_date.$end_date;

        DB::statement(DB::raw("SET @start_date = '$start_date'"));
        DB::statement(DB::raw("SET @end_date = '$end_date'"));
        $dataSecurity['laporan'] = DB::select("
        SELECT 
        a.date AS tanggal,
        (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 4) AS shift_siang,
        (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 8) AS shift_malam
        
        FROM  working_schedule a INNER JOIN working_schedule c ON a.id = c.id INNER JOIN karyawan b
        ON a.karyawan_id = b.id
        WHERE a.date BETWEEN @start_date AND @end_date
        AND a.working_type_id IN ('4','8')
        GROUP BY a.date
        ");
        return view('security/index', $dataSecurity);
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
    public function securitylist(Request $request)
    {

        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $start_cutoff = DB::table('master_cutoff')->get(['start_date']);
        $end_cutoff = DB::table('master_cutoff')->get(['end_date']);
        if ($bulan != 1) {
            $bulan = $bulan - 1;
            $start_date = $tahun . '-' . $bulan . '-' . $start_cutoff[0]->start_date;
        } else {
            $tahun12 = $tahun - 1;
            $bulan = '12';
            $start_date = $tahun12 . '-' . $bulan . '-' . $start_cutoff[0]->start_date;
        }
        $bulan = Carbon::parse($start_date);
        $end_date = $tahun . '-' . $bulan->addMonths(1)->format('m') . '-' . $end_cutoff[0]->end_date;
        //end off format tanggal custom filter
        DB::statement(DB::raw("SET @start_date = '$start_date'"));
        DB::statement(DB::raw("SET @end_date = '$end_date'"));
        $columns = array(
            0 => 'tanggal',
            1 => 'tanggal',
            2 => 'shift_siang',
            3 => 'shift_malam',

        );
        $totalData = DB::select(" select COUNT(a.tanggal) as total from
        (SELECT 
        a.date AS tanggal,
        (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 4) AS shift_siang,
        (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 8) AS Shift_malam
        
        FROM  working_schedule a INNER JOIN working_schedule c ON a.id = c.id INNER JOIN karyawan b
        ON a.karyawan_id = b.id
        WHERE a.date BETWEEN @start_date AND @end_date
        AND a.working_type_id IN ('4','8')
        GROUP BY a.date) a")[0]->total;
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada request live search
        if (empty($request->input('search.value'))) {
            $dataSecurity = DB::select("
            SELECT 
            a.date AS tanggal,
            (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 4) AS shift_siang,
            (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 8) AS shift_malam
            
            FROM  working_schedule a INNER JOIN working_schedule c ON a.id = c.id INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            WHERE a.date BETWEEN @start_date AND @end_date AND  a.deleted_at IS NULL
            AND a.working_type_id IN ('4','8')
            GROUP BY a.date order by $order $dir
            limit $limit offset $start
        ");
        } else {
            $search = $request->input('search.value');
            $dataSecurity = DB::select("
                SELECT 
                a.date AS tanggal,
                (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 4) AS shift_siang,
                (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 8) AS shift_malam
                
                FROM  working_schedule a INNER JOIN working_schedule c ON a.id = c.id INNER JOIN karyawan b
                ON a.karyawan_id = b.id
                WHERE a.date BETWEEN @start_date AND @end_date AND a.deleted_at IS NULL
                AND a.working_type_id IN ('4','8')
                AND a.date LIKE '%$search%'
                or b.nama LIKE '%$search%'
                GROUP BY a.date order by $order $dir
                limit $limit offset $start 
            ");
            $totalFiltered = DB::select("
            SELECT COUNT(a.tanggal) AS filtered FROM
            (SELECT 
            a.date AS tanggal,
            (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 4) AS shift_siang,
            (SELECT b.nama FROM  working_schedule d INNER JOIN karyawan b ON  d.karyawan_id = b.id WHERE d.date = a.date AND d.working_type_id = 8) AS Shift_malam

            FROM  working_schedule a INNER JOIN working_schedule c ON a.id = c.id INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            WHERE a.date BETWEEN @start_date AND @end_date AND a.deleted_at IS NULL
            AND a.working_type_id IN ('4','8')
            GROUP BY a.date) a
            WHERE a.tanggal LIKE '%$search%'
            or shift_siang LIKE '%$search%'
            or shift_malam LIKE '%$search%'
            ")[0]->filtered;
        }


        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataSecurity)) {
            foreach ($dataSecurity as $dt) {
                $row['no'] = $no;
                $hari = Carbon::parse($dt->tanggal)->formatLocalized('%A');
                switch ($hari) {
                    case 'Sunday':
                        $hari = 'Minggu';
                        break;
                    case 'Monday':
                        $hari = 'Senin';
                        break;
                    case 'Tuesday':
                        $hari = 'Selasa';
                        break;
                    case 'Wednesday':
                        $hari = 'Rabu';
                        break;
                    case 'Thursday':
                        $hari = 'Kamis';
                        break;
                    case 'Friday':
                        $hari = 'Jumat';
                        break;
                    case 'Saturday':
                        $hari = 'Sabtu';
                        break;
                }
                $row['hari'] = $hari;
                $row['tanggal'] = $dt->tanggal;
                $row['shift_siang'] = $dt->shift_siang;
                $row['shift_malam'] = $dt->shift_malam;
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
