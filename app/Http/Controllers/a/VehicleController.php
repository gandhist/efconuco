<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Vehicle;
use \App\Employee;
use \App\Beban;
use \App\Vendor;
use \App\Insurance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data['dd'] = Employee::all();
        $data ['bb'] = Beban::all();
        $data["master_vehicle"] = Vehicle::all();
        return view('vehicle/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function vehiclelist(Request $request){
        //    $vehicle= Vehicle::find(22);
        //    return $vehicle->beban->nama;
         
        // definisi orderable column
        $columns = array(
            0 => 'id',
            1 => 'id',
            2 => 'name',
            3 => 'type',
            4 => 'driver_id',
            5 => 'beban_id',
            6 => 'stnk_exp_date',
            7 => 'stnk_biaya',
            8 => 'license_no',
            9 => 'license_exp_date',
            10 => 'license_biaya',
            11 => 'kir_exp_date',
            12 => 'kir_biaya',
            13 => 'insurance_vendor',
            14 => 'insurance_exp_date',
            15 => 'insurance_biaya',
            16 => 'insurance_claim',
            17 => 'insurance_type',
            18 => 'status',
            19 => 'is_ontrip',
            20 => 'is_available',
        );

        $totalData = Vehicle::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            $vehicles = Vehicle::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $vehicles = Vehicle::where('license_no','LIKE', "%{$search}%")
                                    ->orWhere('name','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = Vehicle::where('license_no','LIKE', "%{$search}%")
                                        ->orWhere('name','LIKE',"%{$search}%")
                                        ->count();
        }

        // custom filter query here
        if (!empty($request->input('license_no'))) {
            $vehicles = Vehicle::where('license_no',"$request->license_no")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = Vehicle::where('license_no', "$request->license_no")
                                        ->count();
        }
        if (!empty($request->input('id_vehicle'))) {
            $vehicles = Vehicle::where('id',"$request->id_vehicle")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = Vehicle::where('id', "$request->id_vehicle")
                                        ->count();
        }

        //collection data here
        $data = array();
        $no = 1;
        
        if (!empty($vehicles)) {
            foreach ($vehicles as $vehicle) {
                $edit = route('editvehicle', $vehicle->id);
                $delete = route('deletedvehicle', $vehicle->id);
                $show = route('showvehicle', $vehicle->id);
                $row['no'] = $no;
                $row['name'] = $vehicle->name;
                $row['type'] = $vehicle->type;
                $row['default_bengkel'] = $vehicle->mobil->nama;
                $row['driver_id'] = $vehicle->driver->nama?? '-';;
                $row['beban_id'] = $vehicle->beban->nama?? '-';;
                $row['stnk_exp_date'] = $vehicle->stnk_exp_date;
                $row['stnk_biaya'] = "Rp " . number_format($vehicle->stnk_biaya,2,',','.');
                $row['license_no'] = $vehicle->license_no;
                $row['license_exp_date'] = $vehicle->license_exp_date;
                $row['license_biaya'] = "Rp " . number_format($vehicle->license_biaya,2,',','.');
                $row['kir_exp_date'] = $vehicle->kir_exp_date;
                $row['kir_biaya'] = "Rp " . number_format($vehicle->kir_biaya,2,',','.');
                $row['insurance_vendor'] = $vehicle->asuransi->name;
                $row['insurance_exp_date'] = $vehicle->insurance_exp_date;
                $row['insurance_biaya'] = "Rp " . number_format($vehicle->insurance_biaya,2,',','.');
                $row['insurance_claim'] = "Rp " . number_format($vehicle->insurance_claim,2,',','.');
                $row['insurance_type'] = $vehicle->insurance_type;
                $row['is_ganjil'] = $vehicle->is_ganjil == 0 ? "<span class='label label-success'> Genap </span>" : "<span class='label label-danger'> Ganjil </span> ";
                $row['cc'] = $vehicle->cc;
                $row['tahun_pembuatan'] = $vehicle->tahun_pembuatan;
                $row['pemilik_bpkb'] = $vehicle->pemilik_bpkb;
                $row['volume_tanki'] = $vehicle->volume_tanki;
                $row['warna'] = $vehicle->warna;
                $row['status'] = $vehicle->status == 0 ? "<span class='label label-success'> Active</span>" : "<span class='label label-danger'> Inactive </span> ";
                $row['is_ontrip'] = $vehicle->is_ontrip == 0 ? "<span class='label label-success'>Yes</span>" : "<span class='label label-danger'> No </span> ";
                $row['is_available'] = $vehicle->is_available == 0 ? "<span class='label label-success'> Yes</span>" : "<span class='label label-danger'> No </span> ";
                $row['show'] ="<a href=' $show 'class='btn btn-success' ><span class='fa fa-eye''></span></a>";
                $row['options'] = " 
                <a href=' $edit ' class='btn btn-warning btn-xs'><span class='glyphicon glyphicon-pencil'></span></a>
                <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
                ";
                
                $data[] = $row;
                $no++;
            }
            // return $data;
            
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
    public function create()
    {
        //
        $data['ii'] = Insurance::all();
        $data['mm'] = Vendor::all();
        $data['dd'] = Employee::all();
        $data['bb'] = Beban::all();
        return view('vehicle/create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $error = $request->validate([
            'name'=>'required',
            'type'=>'required',
            'stnk_no'=>'required',
            'stnk_biaya'=>'required',
            'license_no'=>'required',
            'license_biaya'=>'required',
            'insurance_no'=>'required',
            'insurance_vendor'=>'required',
            'insurance_type'=>'required',
            'insurance_claim'=>'required',
            'insurance_biaya'=>'required',
            'insurance_exp_date'=>'required',
            'status'=>'required'
        ]);
        $vehicle = new Vehicle();
        $vehicle->name=$request->get('name');
        $vehicle->type=$request->get('type');
        $vehicle->driver_id = $request->driver_id;
        $vehicle->beban_id = $request->beban_id;
        $vehicle->default_bengkel = $request->default_bengkel;
        if($request->hasFile('picture')){
            $request->file('picture')->move('uploads/cars/',$request->file('picture')->getClientOriginalName());
            $vehicle->picture = $request->file('picture')->getClientOriginalName();
        }
        $vehicle->stnk_no=$request->get('stnk_no');
        $vehicle->stnk_exp_date=$request->get('stnk_exp_date');
        $vehicle->stnk_biaya=$request->get('stnk_biaya');
        $vehicle->license_no=$request->get('license_no');
        $vehicle->license_biaya=$request->get('license_biaya');
        $vehicle->license_exp_date=$request->get('license_exp_date');
        $vehicle->kir_no=$request->get('kir_no');
        $vehicle->kir_exp_date=$request->get('kir_exp_date');
        $vehicle->kir_biaya=$request->get('kir_biaya');
        $vehicle->insurance_no=$request->get('insurance_no');
        $vehicle->insurance_vendor=$request->insurance_vendor;
        $vehicle->insurance_type=$request->get('insurance_type');
        $vehicle->insurance_exp_date=$request->get('insurance_exp_date');
        $vehicle->insurance_biaya=$request->get('insurance_biaya');
        $vehicle->insurance_claim=$request->get('insurance_claim');
        $vehicle->is_ganjil=$request->get('is_ganjil');
        $vehicle->cc=$request->get('cc');
        $vehicle->tahun_pembuatan=$request->get('tahun_pembuatan');
        $vehicle->pemilik_bpkb=$request->get('pemilik_bpkb');
        $vehicle->volume_tanki=$request->get('volume_tanki');
        $vehicle->warna=$request->get('warna');
        $vehicle->status=$request->get('status');
        $vehicle->is_ontrip=$request->get('is_ontrip');
        $vehicle->is_available=$request->get('is_available');
        $vehicle->created_by = Auth::id();
        $vehicle->created_at = Carbon::now()->toDateTimeString();
        $vehicle->save();
        return redirect('/vehicle')->with('success', 'Data berhasil ditambahkan');
        // return $vehicle->all();

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['bio'] = Vehicle::find($id);
        $data['dd'] = Employee::all();
        $data ['bb'] = Beban::all();
        $data ['ii'] = Insurance::all();
        $data['id'] = $id;
        //return $data['bio'];
        return view('vehicle/show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(vehicle $data_vehicle,$id)
    {
        //
        $data["master_vehicle"]= $data_vehicle::find($id);
        $data['mm'] = Vendor::all();
        $data['ii'] = Insurance::all();
        $data['dd'] = Employee::all();
        $data['bb'] = Beban::all();
        return view('vehicle/edit')->with($data);
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
        $vehicle = Vehicle::find($id);
        $vehicle->name=$request->get('name');
        $vehicle->type=$request->get('type');
        $vehicle->driver_id = $request->get('driver_id');
        $vehicle->beban_id = $request->get('beban_id');
        $vehicle->default_bengkel = $request->get('default_bengkel');
        $vehicle->stnk_no=$request->get('stnk_no');
        $vehicle->stnk_exp_date=$request->get('stnk_exp_date');
        $vehicle->stnk_biaya=$request->get('stnk_biaya');
        $vehicle->license_no=$request->get('license_no');
        $vehicle->license_biaya=$request->get('license_biaya');
        $vehicle->license_exp_date=$request->get('license_exp_date');
        $vehicle->kir_no=$request->get('kir_no');
        $vehicle->kir_exp_date=$request->get('kir_exp_date');
        $vehicle->kir_biaya=$request->get('kir_biaya');
        $vehicle->insurance_no=$request->get('insurance_no');
        $vehicle->insurance_vendor=$request->get('insurance_vendor');
        $vehicle->insurance_type=$request->get('insurance_type');
        $vehicle->insurance_exp_date=$request->get('insurance_exp_date');
        $vehicle->insurance_biaya=$request->get('insurance_biaya');
        $vehicle->insurance_claim=$request->get('insurance_claim');
        $vehicle->is_ganjil=$request->get('is_ganjil');
        $vehicle->cc=$request->get('cc');
        $vehicle->tahun_pembuatan=$request->get('tahun_pembuatan');
        $vehicle->pemilik_bpkb=$request->get('pemilik_bpkb');
        $vehicle->volume_tanki=$request->get('volume_tanki');
        $vehicle->warna=$request->get('warna');
        $vehicle->status=$request->get('status');
        $vehicle->is_ontrip=$request->get('is_ontrip');
        $vehicle->is_available=$request->get('is_available');
        $vehicle->updated_by = Auth::id();
        $vehicle->updated_at = Carbon::now()->toDateTimeString();
        $vehicle->save();
        return redirect('/vehicle')->with('success', 'Data berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vehicle_data= Vehicle::find($id);
        $vehicle_data->deleted_by = Auth::id();
        $vehicle_data->deleted_at = Carbon::now()->toDateTimeString();
        $vehicle_data->save();
        return response()->json([
            'success' => 'data berhasil di hapus',

        ]);
    }
}
