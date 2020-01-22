<?php

namespace App\Http\Controllers;
use App\Employee;
use App\WorkingType;
use App\WorkingSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkingScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['wt'] = WorkingType::all();
        return view('karyawan.renja.renja', $data); 
    }

    public function details($mass_id)
    {
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['wt'] = WorkingType::all();
        $data['details'] = DB::select("
        SELECT 
            a.karyawan_id,
            b.nik,
            b.nama,
            MIN(a.date) AS start_date,
            MAX(a.date) AS end_date,
            a.mass_id 
        FROM
            working_schedule a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            WHERE a.deleted_at IS NULL
            and a.mass_id = '$mass_id'
        GROUP BY a.karyawan_id");
        $data['ws'] = WorkingSchedule::where('mass_id',$mass_id)->get();
        if ($data['details']) {
            return view('karyawan.renja.details', $data); 
        }
        else {
            return view('karyawan.renja.renja', $data); 
        }
    }

    public function renjaGroupedList(Request $request)
    {
        $nik = $request->input('filter_nik');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');
        $working_type = $request->input('filter_wt');

        $columns = array(
            0 => 'a.mass_id',
            1 => 'a.mass_id',
            2 => 'b.nik',
            3 => 'b.nama',
            4 => 'start_date',
            5 => 'end_date',
        );
        
        $totalData = DB::select(" select COUNT(a.mass_id) as total FROM (
            SELECT mass_id FROM working_schedule WHERE deleted_at IS NULL
            GROUP BY mass_id, karyawan_id) a")[0]->total;
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada request live search
        if (empty($request->input('search.value'))) {
            $dataRenja = DB::select("
                SELECT 
                    a.karyawan_id,
                    b.nik,
                    b.nama,
                    MIN(a.date) AS start_date,
                    MAX(a.date) AS end_date,
                    a.mass_id 
                FROM
                    working_schedule a
                    INNER JOIN karyawan b
                    ON a.karyawan_id = b.id
                    WHERE a.deleted_at IS NULL
                GROUP BY a.mass_id 
                order by $order $dir
                limit $limit offset $start
            ");
        }
        else {
            $search = $request->input('search.value');
            $dataRenja = DB::select("
                SELECT 
                    a.karyawan_id,
                    b.nik,
                    b.nama,
                    MIN(a.date) AS start_date,
                    MAX(a.date) AS end_date,
                    a.mass_id 
                FROM
                    working_schedule a
                    INNER JOIN karyawan b
                    ON a.karyawan_id = b.id
                    WHERE a.deleted_at IS NULL
                    and b.nama like '%$search%'
                    or b.nik like '%$search%'
                    GROUP BY a.mass_id 
                order by $order $dir
                limit $limit offset $start
            ");

            $totalFiltered = DB::select("
            SELECT COUNT(a.nik) AS filtered FROM
                (SELECT 
                b.nama,
                b.nik 
                FROM
                working_schedule a 
                INNER JOIN karyawan b 
                    ON a.karyawan_id = b.id 
                WHERE a.deleted_at IS NULL 
                GROUP BY a.mass_id ) a
                WHERE a.nik LIKE '%$search%'
                or a.nama like '%$search%'
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($start_date) || !empty($end_date) || !empty($working_type) ) {
            $search = $request->input('search.value');
            $nik = (!empty($nik)) ? "and karyawan_id = '$nik'" : '' ;
            $working_type = (!empty($working_type)) ? "and working_type_id = '$working_type'" : '' ;
            if (!empty($start_date) && !empty($end_date) ) {
                $period = " and date between '$start_date' and '$end_date' ";
            }
            else {
                $period = '';
            }

            $dataRenja = DB::select("
                SELECT 
                    a.karyawan_id,
                    b.nik,
                    b.nama,
                    MIN(a.date) AS start_date,
                    MAX(a.date) AS end_date,
                    a.mass_id 
                FROM
                    working_schedule a
                    INNER JOIN karyawan b
                    ON a.karyawan_id = b.id
                    WHERE a.deleted_at IS NULL 
                    and a.mass_id in (SELECT mass_id FROM working_schedule WHERE deleted_at IS NULL $nik $working_type $period)
                    GROUP BY a.mass_id 
                order by $order $dir
                limit $limit offset $start
            ");


            $totalFiltered = DB::select("
                SELECT count(a.id) as filtered FROM working_schedule a 
                LEFT JOIN 
                (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM working_type a LEFT JOIN working_hour b
                ON a.id = b.working_type_id ) b 
                ON a.working_type_id = b.id
                INNER JOIN karyawan c 
                ON a.karyawan_id = c.id
                where a.deleted_at is null
                $nik
                $working_type
                $period
                ")[0]->filtered;

        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataRenja)) {
            foreach ($dataRenja as $ro) {
                $edit = url('absensi/renja/details', $ro->mass_id);
                $row['bulkDelete'] = "<input type='checkbox' name='deleteAll[]' onclick='partialSelected()' class='bulkDelete' id='bulkDeleteName' value='$ro->mass_id'>";
                //$row['bulkDelete'] = '<input type="checkbox" name="deleteAll[]" onclick="partialSelected()" class="bulkDelete" id="bulkDeleteName" value="$ro->mass_id">';
                $row['no'] = $no;
                $row['nik'] = $ro->nik;
                $row['nama'] = $ro->nama;
                $row['start_date'] = $ro->start_date;
                $row['end_date'] = $ro->end_date;
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

    public function renjalist(Request $request)
    {
        $nik = $request->input('filter_nik');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');
        $working_type = $request->input('filter_wt');

        $columns = array(
            0 => 'a.id',
            1 => 'c.nik',
            2 => 'c.nama',
            3 => 'a.date',
            4 => 'b.nama',
            5 => 'b.start_time',
            6 => 'b.end_time'
        );
        
        $totalData = WorkingSchedule::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $dataRenja = DB::select("
                SELECT a.id, a.karyawan_id, c.nik, c.nama, a.date, a.working_type_id, a.mass_id, b.nama AS working_type , b.day, b.start_time, b.end_time FROM working_schedule a 
                LEFT JOIN 
                (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM working_type a LEFT JOIN working_hour b
                ON a.id = b.working_type_id ) b 
                ON a.working_type_id = b.id
                INNER JOIN karyawan c 
                ON a.karyawan_id = c.id
                where a.deleted_at is null
                order by $order $dir
                limit $limit offset $start
            ");
        }
        else {
            $search = $request->input('search.value');
                $dataRenja = DB::select("
                    SELECT a.id, a.karyawan_id, c.nik, c.nama, a.date, a.working_type_id, a.mass_id, b.nama AS working_type , b.day, b.start_time, b.end_time FROM working_schedule a 
                    LEFT JOIN 
                    (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM working_type a LEFT JOIN working_hour b
                    ON a.id = b.working_type_id ) b 
                    ON a.working_type_id = b.id
                    INNER JOIN karyawan c 
                    ON a.karyawan_id = c.id
                    where a.deleted_at is null
                    and c.nama like '%$search%'
                    or c.nik like '%$search%'
                    or b.nama like '%$search%'
                    ORDER BY $order  $dir
                    LIMIT $limit OFFSET $start
                ");
            $totalFiltered = DB::select("
                SELECT count(a.id) as filtered FROM working_schedule a 
                LEFT JOIN 
                (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM working_type a LEFT JOIN working_hour b
                ON a.id = b.working_type_id ) b 
                ON a.working_type_id = b.id
                INNER JOIN karyawan c 
                ON a.karyawan_id = c.id
                where a.deleted_at is null
                and c.nama like '%$search%'
                or c.nik like '%$search%'
                or b.nama like '%$search%'
                order by $order $dir
                limit $limit offset $start
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($start_date) || !empty($end_date) || !empty($working_type) ) {
            $nik = (!empty($nik)) ? "and a.karyawan_id = '$nik'" : '' ;
            $working_type = (!empty($working_type)) ? "and a.working_type_id = '$working_type'" : '' ;
            if (!empty($start_date) && !empty($end_date) ) {
                $period = " and a.date between '$start_date' and '$end_date' ";
            }
            else {
                $period = '';
            }

            $dataRenja = DB::select("
            SELECT a.id, a.karyawan_id, c.nik, c.nama, a.date, a.working_type_id, a.mass_id, b.nama AS working_type , b.day, b.start_time, b.end_time FROM working_schedule a 
            LEFT JOIN 
            (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM working_type a LEFT JOIN working_hour b
            ON a.id = b.working_type_id ) b 
            ON a.working_type_id = b.id
            INNER JOIN karyawan c 
            ON a.karyawan_id = c.id
            where a.deleted_at is null
            $nik
            $working_type
            $period
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
        ");
        $totalFiltered = DB::select("
            SELECT count(a.id) as filtered FROM working_schedule a 
            LEFT JOIN 
            (SELECT a.id, a.nama, a.lembur_id, b.day, b.start_time, b.end_time FROM working_type a LEFT JOIN working_hour b
            ON a.id = b.working_type_id ) b 
            ON a.working_type_id = b.id
            INNER JOIN karyawan c 
            ON a.karyawan_id = c.id
            where a.deleted_at is null
            $nik
            $working_type
            $period
            ")[0]->filtered;

        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataRenja)) {
            foreach ($dataRenja as $ro) {
                $edit = route('renja.edit', $ro->mass_id);
                $delete = route('renja.destroy', $ro->id);

                $row['no'] = $no;
                $row['nik'] = $ro->nik;
                $row['nama'] = $ro->nama;
                $row['tanggal'] = $ro->date;
                $row['working_type'] = $ro->working_type;
                $row['start_time'] = $ro->start_time;
                $row['end_time'] = $ro->end_time;
                $row['options'] = "
                <button class='btn btn-xs btn-warning' onclick='edit($ro->mass_id)'><span class='glyphicon glyphicon-pencil'></span></button>
                <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
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


        // $ws = WorkingSchedule::all();
        // foreach ($ws as $key) {
        //     echo $key->karyawan_id;
        //     echo $key->karyawan['nama'];
        //     echo "<br>";
        //     echo $key->date;
        //     echo "<br>";
        //     echo $key->working_type['nama'];
        //     echo "<br>";
        //     //echo $key->working_type->working_hour;
        //     foreach ($key->working_type->working_hour as $schedule) {
        //         echo $schedule->start_time;
        //         echo $schedule->end_time;
        //     }
        //     echo "<hr>";
        // }
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
        $start_date = Carbon::parse($request->input('start_date_store'));
        $end_date = Carbon::parse($request->input('end_date_store'));
        $period = CarbonPeriod::create($start_date, $end_date);
        $i = 0;
        foreach ($period as $key => $value) {
            $validate['working_type'.$i] = 'required';
            $i++;
        }
        $request->validate($validate);

        if ( WorkingSchedule::where('karyawan_id',$request->nik_store)->whereBetween('date', [$request->start_date_store, $request->end_date_store])->whereNull('deleted_at')->count() > 0 ) {
            return response()->json([
                'status' => false,
                'message' => 'Data sudah ada di database', 
            ]);    
        }

        $data = new WorkingSchedule();
        $mass_id = Carbon::now()->timestamp;

        $ite = 0;
        foreach ($period as $key => $date) {
            $tanggal = "date".$ite;
            $wt = "working_type".$ite;
            if ($request->has($tanggal) && $request->has($wt) ) {
                $dates[] = [
                    'karyawan_id' => $request->nik_store,
                    'date' => $request->input("date".$ite),
                    'working_type_id' => $request->input("working_type".$ite),
                    'mass_id' => $mass_id,
                    'created_by' => Auth::id(),
                    'created_at' => Carbon::now()->toDateTimeString(),
                ];
            }
            $ite++;
        }
        $save = $data->insert($dates);
        if ($save) {
            return response()->json([
                'status' => true,
                'message' => 'data berhasil di generate.'
            ]);
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'data gagal di generate.'
            ]);
        }
        // return $dates;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['tanggal'] = WorkingSchedule::where('mass_id', $id)->get(['date','id','karyawan_id','working_type_id']);
        $data['start_date'] = WorkingSchedule::where('mass_id', $id)->min('date');
        $data['end_date'] = WorkingSchedule::where('mass_id', $id)->max('date');
        $data['mass_id'] = $id;
        return $data;
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
        //return $request->all();
        $mass_id = Carbon::now()->timestamp;
        $start_date = Carbon::parse($request->input('start_date_store'));
        $end_date = Carbon::parse($request->input('end_date_store'));
        $period = CarbonPeriod::create($start_date, $end_date);
        $i = 0;
        foreach ($period as $key => $value) {
            $validate['working_type'.$i] = 'required';
            $i++;
        }
        $request->validate($validate);
        
        $ite = 0;
        foreach ($period as $key => $date) {
            $tanggal = "date".$ite;
            $wt = "working_type".$ite;
            $id = "id".$ite;
            if ($request->has($tanggal) && $request->has($wt) ) {
               $dates = [
                    // 'karyawan_id' => $request->nik_store,
                    // 'date' => $request->input("date".$ite),
                    // 'mass_id' => $mass_id,
                    'working_type_id' => $request->input("working_type".$ite),
                    'updated_by' => Auth::id(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ];

                $save = WorkingSchedule::where('id',$request->input("id".$ite))->update($dates); // update data period
            }
            $ite++;
        }
        if ($save) {
            return response()->json([
                'status' => true,
                'message' => 'data berhasil di Upadte.'
            ]);
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'data gagal di Update.'
            ]);
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
        $ws = WorkingSchedule::find($id);
        $ws->deleted_by = Auth::id();
        $ws->deleted_at = Carbon::now()->toDateTimeString();
        $ws->save();
        return response()->json([
            'status' => true,
            'message' => 'data berhasil di Delete',
        ]);
    }

    // mass delete
    public function mass_delete(Request $request)
    {
        $data = [
            'deleted_by' => Auth::id(),
            'deleted_at' => Carbon::now()->toDateTimeString()
        ];
        $delete = DB::table('working_schedule')->whereIn($request->delete_by,$request->id)->update($data);
        if ($delete) {
            return response()->json([
                'status' => true,
                'message' => 'data yang dipilih sudah di delete!!'
                ]);
        }
    }

    // validasi periode 
    public function _validate_periode(Request $request)
    {
        Carbon::setLocale('id');
        $request->validate([
            'nik' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        if ( WorkingSchedule::where('karyawan_id',$request->nik)->whereBetween('date', [$request->start_date, $request->end_date])->whereNull('deleted_at')->count() > 0 ) {
            $data = WorkingSchedule::where('karyawan_id',$request->nik)->whereBetween('date', [$request->start_date, $request->end_date])->whereNull('deleted_at')->get();
            return response()->json([
                'status' => false,
                'message' => 'Data sudah ada di database', 
                'data' => $data, 
            ]);    
        }



        $start_date = Carbon::parse($request->input('start_date'));
        $end_date = Carbon::parse($request->input('end_date'));
        $data['total_day'] = $end_date->diffInDays($start_date)+1;
        $data['day_name'] = $end_date->format('l');
        // generate periode selected
        $data['period'] = CarbonPeriod::create($start_date, $end_date);
        $dates = [];
        foreach ($data['period'] as $key => $date) {
            $dates[] = $date->format('l,Y-m-d');
        }
        $data['tanggal'] = $dates;
        // mendapatkan default working_type
        $data['default_wt'] = Employee::find($request->nik)->working_type_id;

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
