<?php

namespace App\Http\Controllers;
use App\Vendor;
use \App\Category;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendor['category'] = Category::all();
        $vendor['vd'] = Vendor::all();
        return view('vendor/index')->with($vendor);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $data['category'] = Category::all();
        return view('vendor/create',$data);
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
            'nama'=>'required',
            'alamat'=>'required',
            'contact_person'=>'required',
            'phone_no'=>'required',
            'email'=>'required',
            'bank_1'=>'required',
            'bank_account_1'=>'required',
            'bank_rekening_1'=>'required',
            
        ]);
        $vendor = new Vendor();
        $vendor->category_id = $request->category_id;
        $vendor->nama=$request->get('nama');
        $vendor->alamat=$request->get('alamat');
        $vendor->contact_person=$request->get('contact_person');
        $vendor->phone_no=$request->get('phone_no');
        $vendor->email=$request->get('email');
        $vendor->bank_1=$request->get('bank_1');
        $vendor->bank_account_1=$request->get('bank_account_1');
        $vendor->bank_rekening_1=$request->get('bank_rekening_1');
        $vendor->bank_2=$request->get('bank_2');
        $vendor->bank_account_2=$request->get('bank_account_2');
        $vendor->bank_rekening_2=$request->get('bank_rekening_2');
        $vendor->keterangan=$request->get('keterangan');
        $vendor->created_by = Auth::id();
        $vendor->created_at = Carbon::now()->toDateTimeString();
        $vendor->save();
        return redirect('/listVendor')->with('success', 'Data berhasil ditambahkan');
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
    public function edit(vendor $vendor,$id)
    {
        //
        $data["master_vendor"] = $vendor::find($id);
        $data['category'] = Category::all();
        return view('vendor/edit')->with($data);
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
            'nama'=>'required',
            'alamat'=>'required',
            'contact_person'=>'required',
            'phone_no'=>'required',
            'email'=>'required',
            'bank_1'=>'required',
            'bank_account_1'=>'required',
            'bank_rekening_1'=>'required',
            
        ]);
        $vendor =  Vendor::find($id);
        $vendor->category_id = $request->get('category_id');
        $vendor->nama=$request->get('nama');
        $vendor->alamat=$request->get('alamat');
        $vendor->contact_person=$request->get('contact_person');
        $vendor->phone_no=$request->get('phone_no');
        $vendor->email=$request->get('email');
        $vendor->bank_1=$request->get('bank_1');
        $vendor->bank_account_1=$request->get('bank_account_1');
        $vendor->bank_rekening_1=$request->get('bank_rekening_1');
        $vendor->bank_2=$request->get('bank_2');
        $vendor->bank_account_2=$request->get('bank_account_2');
        $vendor->bank_rekening_2=$request->get('bank_rekening_2');
        $vendor->keterangan=$request->get('keterangan');
        $vendor->updated_by = Auth::id();
        $vendor->updated_at = Carbon::now()->toDateTimeString();
        $vendor->save();
        return redirect('/listVendor')->with('success', 'Data berhasil di Update');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(vendor $vendor,$id)
    {
        //
        $vendor= Vendor::find($id);
        $vendor->deleted_by = Auth::id();
        $vendor->deleted_at = Carbon::now()->toDateTimeString();
        if($vendor->save()){
            return response()->json([
                'success' => 'Beban berhasil dihapus'
            ]);
        }
        
    }
}
