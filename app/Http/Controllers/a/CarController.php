<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cars;
use Validator;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    public function index(){
        return view('car/index');
    }

    public function getAll(){
        $data = Cars::get();
        return response()->json(['data'=>$data]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'license' => 'required',
            // 'picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'required'
        ]);

        
        if($validator->passes()) {
            $input = $request->all();
            $input['is_ontrip'] = 0;
            $input['created_by'] = Auth::id();
            $input['is_active'] = $request->active=="on"?1:0;
            $input['is_available'] = 0;
            if(!$request->hasFile('picture'))
                $input['picture'] = '';
            else{
                $input['picture'] = $request->name.'.'.$request->picture->getClientOriginalExtension();
                $request->picture->move(public_path('uploads/cars'), $input['picture']);
            }

            Cars::create($input);
            return response()->json(['status'=>'success']);
        }

        return response()->json(['status'=>$validator->errors()->all()]);
    }

     public function update(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'license' => 'required',
            // 'picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'required'
        ]);

        
        if($validator->passes()) {
            $cars = Cars::where('id', '=', $id)->first();
            $input = $request->all();
            if(!$request->hasFile('picture'))
                $input['picture'] = '';
            else{
                $input['picture'] = $request->name.'.'.$request->picture->getClientOriginalExtension();
                $request->picture->move(public_path('uploads/cars'), $input['picture']);
            }

            $cars->update($input);
            return redirect('/cars')->with('success', 'Car has been updated');
        }

        return response()->json(['status'=>$validator->errors()->all()]);
    }

    public function destroy($id){
        $item = Cars::findOrFail($id);
        $item->delete();

        return response()->json(['status'=>'berhasil hapus']);
    }

    public function show($id){
        $car = Cars::findOrFail($id);
        return response()->json(['data' => $car], 200);
    }

    public function edit($id){
        $car = Cars::findOrFail($id);
        return view('car/edit', [
            'car' => $car
        ]);
    }

    // public function update(Request $request, $id) {
    //     $car = Cars::findOrFail($id);
    //      if($request->hasFile('picture') && $request->file('picture')->isValid()){
    //         $filename = strtolower(trim($request->name)).".".$request->file('picture')->extension();
    //         // $request->file('picture')->storeAs('images', $filename);
    //         // echo $filename."berhasil";
    //         $request->file('picture')->move(public_path('uploads/drivers'), $filename);
    //     }
    // }

    // UPDATE AVAILABLE FUNCTION
    public function available(Request $request, $id){
        $data = Cars::findOrFail($id);
        $status = $data->update($request->all());
        return response()->json(['status' => true], 200);
    }
}
