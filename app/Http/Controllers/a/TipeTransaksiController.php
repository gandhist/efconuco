<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TipeTransaksi;
use Validator;
use Illuminate\Support\Facades\Auth;

class TipeTransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            return datatables()->of(TipeTransaksi::latest()->get())
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $url = "'" . route('tipetransaksi.edit', $data->id) . "'";
                    $button = '<a  class="btn btn-warning btn-xs edit" onclick="goshow(' . $url . ')"><span class="glyphicon glyphicon-pencil"></span>Edit</a>';
                    $button .= '&nbsp;&nbsp;';
                    $button .= '<button type="button" name="delete" data-id="' . $data->id . '" class="btn btn-xs btn-danger delete"><span class="glyphicon glyphicon-trash"></span>Delete</button>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('tipetransaksi.index');
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
        $rules = [
            'name' => 'required',
            'status' => 'required',
        ];

        $error = Validator::make($request->all(), $rules);

        if ($error->fails()) {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $form_data = [
            'name' => $request->name,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'updated_by' => null,
            'deleted_by' => null
        ];

        TipeTransaksi::create($form_data);

        return response()->json(['success' => 'Data Tipe Transaksi berhasil ditambah!']);
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
        if (request()->ajax()) {
            $data = TipeTransaksi::findOrFail($id);
            return response()->json(['data' => $data]);
        }
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
        $rules = [
            'name' => 'required',
            'status' => 'required',
        ];

        $error = Validator::make($request->all(), $rules);

        if ($error->fails()) {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $form_data = [
            'name' => $request->name,
            'status' => $request->status,
            'created_by' => null,
            'updated_by' => Auth::id(),
            'deleted_by' => null
        ];

        TipeTransaksi::whereId($id)->update($form_data);

        return response()->json(['success' => 'Data Tipe Transaksi berhasil dirubah!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // return $id;
        $deleted_by = Auth::id();
        TipeTransaksi::where('id', $id)
            ->update(['status' => 2, 'deleted_by' => $deleted_by]);

        TipeTransaksi::destroy($id);

        return response()->json([
            'status' => true,
            'message' => 'Data Tipe Transaksi berhasil di hapus!'
        ]);
    }
}
