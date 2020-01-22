<?php

namespace App\Http\Controllers;

use App\WorkingHour;
use App\WorkingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkingHourController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $workingdata = DB::table('working_hour')
            ->join('working_type', 'working_hour.working_type_id', '=', 'working_type.id')
            ->select('working_hour.*', 'working_type.nama')
            ->get();
        return view('workinghour.index', ['workingdata' => $workingdata]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $workingtype = WorkingType::all();
        return view('workinghour/create', compact('workingtype'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        WorkingHour::create($request->all());
        return redirect('/workinghour')->with('status', 'Data Jam Kerja Berhasil Ditambahkan!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\WorkingHour  $workingHour
     * @return \Illuminate\Http\Response
     */
    public function show(WorkingHour $workingHour)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\WorkingHour  $workingHour
     * @return \Illuminate\Http\Response
     */
    public function edit(WorkingHour $workingHour)
    {
        $workingType = WorkingType::all();
        return view('workinghour.edit', ['workingHour' => $workingHour, 'workingType' => $workingType]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\WorkingHour  $workingHour
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WorkingHour $workingHour)
    {
        WorkingHour::where('id', $workingHour->id)
            ->update([
                'day' => $request->day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'working_type_id' => $request->working_type_id,
                'late_tolerance' => $request->late_tolerance
            ]);
        return redirect('/workinghour')->with('status', 'Data Jam Kerja Berhasil Diubah!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\WorkingHour  $workingHour
     * @return \Illuminate\Http\Response
     */
    public function destroy(WorkingHour $workingHour)
    {
        WorkingHour::destroy($workingHour->id);
        return redirect('/workinghour')->with('status', 'Data Jam Kerja Berhasil Dihapus!');
    }
}
