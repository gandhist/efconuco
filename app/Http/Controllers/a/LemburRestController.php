<?php

namespace App\Http\Controllers;

use App\Kantor;
use App\Lembur;
use App\LemburRest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LemburRestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data["master_lembur_rest"] = LemburRest::all();
        return view('lemburrest/index')->with($data);
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['dd_kantor'] = Kantor::all();
        $data['dd_lembur'] = Lembur::all();
        $data['dd_lemburrest'] = LemburRest::all();
        return view('lemburrest/create',$data);
        $master_lembur_rest->created_at = Carbon::now()->toDateTimeString();
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
         $error = $request->validate([
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        if ($error) {
        $master_lembur_rest= new LemburRest();
        $master_lembur_rest->start_time= $request->get('start_time');
        $master_lembur_rest->end_time= $request->get('end_time');
        if($master_lembur_rest->save())
        return redirect('/lemburrest')->with('success', 'LemburRest berhasil ditambahkan');
        else
        return redirect('/lemburrest')->with('error', 'An error occurred');
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\LemburRest
     * @return \Illuminate\Http\Response
     */
    public function show(LemburRest $master_lembur_rest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\LemburRest
     * @return \Illuminate\Http\Response
     */
    public function edit(LemburRest $master_lembur_rest,$id)
    {
        $dd_lemburrest= LemburRest::all();
        $data['master_lembur_rest'] = $master_lembur_rest::find($id);
        return view('lemburrest/edit',['dd'=> $dd_lemburrest])->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\LemburRest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $error = $request->validate([
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        $master_lembur_rest= LemburRest::find($id);
        $master_lembur_rest->start_time= $request->get('start_time');
        $master_lembur_rest->end_time= $request->get('end_time');
        if($master_lembur_rest->save())
            return redirect('/lemburrest')->with('success', 'LemburRest berhasil diupdate');
        else
            return redirect('/lemburrest')->with('error', 'An error occurred');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\LemburRest
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $master_lembur_rest = LemburRest::find($id);

        if($master_lembur_rest->delete()){
            return response()->json([
                'success' => 'Lembur Rest berhasil dihapus'
            ]);
        } else {
            return response()->json([
                'error' => 'An error occurred'
            ]);
        }
    }
}
