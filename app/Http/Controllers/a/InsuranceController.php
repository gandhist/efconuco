<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Insurance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InsuranceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data["master_insurance"] = Insurance::all();
        return view('insurance/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('insurance/create');
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
        $request->validate([
            'name'=>'required',
            'pic'=>'required',
            'contact'=>'required',
            'address'=>'required'
        ]);
        $insurance = new Insurance();
        $insurance->name=$request->get('name');
        $insurance->pic=$request->get('pic');
        $insurance->contact=$request->get('contact');
        $insurance->address=$request->get('address');
        $insurance->created_by = Auth::id();
        $insurance->created_at = Carbon::now()->toDateTimeString();
        $insurance->save();
        return redirect('/insurance')->with('success', 'Data berhasil ditambahkan');


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
    public function edit(insurance $data_insurance,$id)
    {
        $data["master_insurance"] = $data_insurance::find($id);
        return view('insurance/edit')->with($data);
        
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
        $request->validate([
            'name'=>'required',
            'pic'=>'required',
            'contact'=>'required',
            'address'=>'required'
        ]);
        $insurance = Insurance::find($id);
        $insurance->name=$request->get('name');
        $insurance->pic=$request->get('pic');
        $insurance->contact=$request->get('contact');
        $insurance->address=$request->get('address');
        $insurance->updated_by = Auth::id();
        $insurance->updated_at = Carbon::now()->toDateTimeString();
        $insurance->save();
        return redirect('/insurance')->with('success', 'Data berhasil Update');

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
        $insurance= Insurance::find($id);
        $insurance->deleted_by = Auth::id();
        $insurance->deleted_at = Carbon::now()->toDateTimeString();

        if($insurance->save()){
            return response()->json([
                'success' => 'Insurance berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
