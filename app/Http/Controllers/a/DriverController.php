<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Drivers;

class DriverController extends Controller
{
    public function index(){
        $data['cars'] = Drivers::leftJoin("cars",'drivers.default_car_id','=','cars.id')
        ->selectRaw('
            drivers.id,
            drivers.nik,
            drivers.name,
            drivers.username,
            drivers.no_telp,
            drivers.is_ontrip,
            drivers.is_active,
            IF(drivers.is_available = 1, "AVAILABLE", "UNAVAILABLE") as is_available,
            cars.name as default_car')
        ->get();
        return view('driver/index')->with($data);
    }

    public function formInput(){
        $data['cars'] = \App\Cars::all();
        return view('driver/form')->with($data);
    }

    public function getAll(){
        $data = Drivers::leftJoin("cars",'drivers.default_car_id','=','cars.id')
        ->selectRaw('
            drivers.id,
            drivers.nik,
            drivers.name,
            drivers.username,
            drivers.no_telp,
            drivers.is_ontrip,
            drivers.is_active,
            drivers.is_available,
            cars.name as default_car')
        ->get(); 

        return response()->json(['data'=>$data]);
    }

    public function create(Request $request){
        $filename = '';
        
        if($request->hasFile('picture') && $request->file('picture')->isValid()){
            $filename = $request->nik.".".$request->file('picture')->extension();
            // $request->file('picture')->storeAs('images', $filename);
            // echo $filename."berhasil";
            $request->file('picture')->storeAs('drivers', $filename);
        }

        $save = Drivers::create([
            'nik' => $request->nik,
            'name' => $request->name,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'default_car_id' => $request->car_id,
            'picture' => $filename,
            'no_telp' => $request->no_telp,
            'is_available' => 1,
            'is_ontrip' => 0,
            'is_active' => ($request->active=="on"?1:0),
            'created_by' => Auth::id()
        ]);
        // if($save)
        return redirect('drivers');

        //return response()->json(['status'=>'error']);
    }

    public function destroy($id){
        $item = Drivers::findOrFail($id);
        $item->delete();

        return response()->json(['status'=>'berhasil hapus']);
    }

    public function edit($id){
        $data['cars'] = \App\Cars::all();
        $data["drivers"] = Drivers::where('id', '=', $id)->first();
        return view('driver/update')->with($data);
    }

    public function update(Request $request, $id){
        $filename = '';
        $drivers = Drivers::where('id', '=', $id)->first();
        if($request->hasFile('picture') && $request->file('picture')->isValid()){
            $filename = $request->nik.".".$request->file('picture')->extension();
            // $request->file('picture')->storeAs('images', $filename);
            // echo $filename."berhasil";
            $request->file('picture')->storeAs('drivers', $filename);
        }

        $save = $drivers->update([
            'nik' => $request->nik,
            'name' => $request->name,
            'username' => $request->username,
            'password' => ($request->password != null ? bcrypt($request->password) : $drivers->password),
            'default_car_id' => $request->car_id,
            'picture' => $filename,
            'no_telp' => $request->no_telp,
            'is_available' => 1,
            'is_ontrip' => 0,
            'is_active' => ($request->active=="on"?1:0)
        ]);
        // if($save)
        return redirect('drivers');
    }

    // UPDATE AVAILABLE FUNCTION
    public function available(Request $request, $id){
        $data = Drivers::findOrFail($id);
        $status = $data->update($request->all());
        return response()->json(['status' => true], 200);
    }
}
