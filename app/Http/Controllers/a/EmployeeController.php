<?php

namespace App\Http\Controllers;

use \App\Employee;
use \App\Provinsi;
use \App\Kota;
use \App\Level;
use \App\Beban;
use \App\Kantor;
use \App\Divisi;
use \App\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use File;

class EmployeeController extends Controller
{
    /**kas 
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $response)
    {
        //
        $emp = Employee::all();
        // LOV provinsi dlsb di definisikan di sini dan di push ke array yang di kirim ke view
        return view('karyawan.index', ['emp'=>$emp]); 
     
    }

    public function empList(Request $request){

        // definisi orderable column
        $columns = array(
            0 => 'id',
            1 => 'id',
            2 => 'nama',
            3 => 'alamat',
            4 => 'email',
            5 => 'handphone',
            6 => 'status',
        );

        $totalData = Employee::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            $employees = Employee::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $employees = Employee::where('nik','LIKE', "%{$search}%")
                                    ->orWhere('nama','LIKE',"%{$search}%")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = Employee::where('nik','LIKE', "%{$search}%")
                                        ->orWhere('nama','LIKE',"%{$search}%")
                                        ->count();
        }

        // custom filter query here
        if (!empty($request->input('nik'))) {
            $employees = Employee::where('nik',"$request->nik")
                                    ->offset($start)
                                    ->limit($limit)
                                    ->orderBy($order, $dir)
                                    ->get();

            $totalFiltered = Employee::where('nik', "$request->nik")
                                        ->count();
        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($employees)) {
            foreach ($employees as $employee) {
                $edit = route('employee.edit', $employee->id);
                $delete = route('employee.destroy', $employee->id);

                $row['no'] = $no;
                $row['nik'] = $employee->nik;
                $row['nama'] = $employee->nama;
                $row['alamat'] = $employee->alamat;
                $row['email'] = $employee->email;
                $row['handphone'] = $employee->handphone;
                $row['status'] = $employee->status == 1 ? "<span class='label label-success'> Active</span>" : "<span class='label label-danger'> Inactive </span> ";
                $row['options'] = " 
                <a href=' $edit ' class='btn btn-warning btn-xs'><span class='glyphicon glyphicon-pencil'></span></a>
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
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $data['dd'] = Employee::all();
        $data['dd_prov'] = Provinsi::all();
        $data['dd_kota'] = Kota::all();
        $data['dd_level'] = Level::all();
        $data['dd_kantor'] = Kantor::all();
        $data['dd_divisi'] = Divisi::all();
        $data['dd_jabatan'] = Jabatan::all();
        $data['dd_beban'] = Beban::all();
        $data['dd_working_type'] = DB::select('select id, nama from working_type');
        $data['dd_ptkp'] = DB::select('select id, nama_ptkp from master_ptkp');
        return view('karyawan.create',$data);
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
            'nik' => 'required|regex:/^[0-9]+$/',
            'nama' => 'required',
            'email' => 'email',
            'alamat_ktp' => 'required',
            'provinsi_ktp' => 'required',
            'kota_ktp' => 'required',
            'alamat_domisili' => 'required',
            'provinsi_domisili' => 'required',
            'kota_domisili' => 'required',
            'npwp' => 'required|regex:/^[0-9.-]+$/',
            'bpjs' => 'required|regex:/^[0-9.-]+$/',
            'beban_id' => 'required',
            'jenis_kelamin' => 'required',
            'handphone' => 'required',
            'doj' => 'required',
            'foto' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG|max:5120'
        ]);

        if ($error) {
            $emp = new Employee;

            $emp->nik = $request->nik;
            $emp->nama = $request->nama;
            $emp->alamat_ktp = $request->alamat_ktp;
            $emp->alamat_ktp_provinsi_id = $request->provinsi_ktp;
            $emp->alamat_ktp_kota_id = $request->kota_ktp;
            $emp->alamat = $request->alamat_domisili;
            $emp->alamat_kota_id = $request->kota_domisili;
            $emp->alamat_provinsi_id = $request->provinsi_domisili;

            $emp->npwp = $request->npwp;
            $emp->email = $request->email;
            $emp->agama = $request->agama;
            $emp->handphone = $request->handphone;
            $emp->keterangan = $request->keterangan;
            $emp->date_birth = $request->dob;
            $emp->date_joining = $request->doj;
            $emp->date_resign = $request->date_resign;
            $emp->working_type_id = $request->working_type;
            $emp->status_pernikahan = $request->status_pernikahan;
            $emp->ptkp = $request->ptkp;
            $emp->bpjs = $request->bpjs;
            $emp->jenis_kelamin = $request->jenis_kelamin;
            $emp->tempat_lahir = $request->tempat_lahir;

            $emp->level_id = $request->level;
            $emp->working_area = $request->working_area;
            $emp->beban_id = $request->beban_id;
            $emp->beban_id_kaskecil = $request->beban_id_kaskecil;
            $emp->divisi_id = $request->divisi_id;
            $emp->jabatan_id = $request->jabatan_id;
            $emp->is_pengurus = $request->is_pengurus;
            $emp->status = $request->status;

            if ($files = $request->file('foto')) {
                $destinationPath = 'uploads/employees/'; // upload path
                $profileImage = Str::slug($request->nik,'_') . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $profileImage);
                $emp->foto = $profileImage;
             }

            $emp->created_by = Auth::id();
            $emp->created_at = Carbon::now()->toDateTimeString();
            $save = $emp->save();
            if ($save) {
                // mendapatkan id terakhir dari karyawan yang baru di input, untuk mendapatkan id nya lalu di input sebagai id di table karyawan login
                // karyawan.id = karyawan_login.id
                $id = DB::table('karyawan')->where('nik', '=', $request->nik)->limit(1)->orderBy('id','desc')->get(['id']);
                $user_login = new \App\KaryawanLoginModel();
                // user login mobile apps
                $user_login->id = $id[0]->id;
                $user_login->nik_karyawan = $request->nik;
                $user_login->username = $request->handphone;
                $user_login->password = Hash::make($request->password);
                $user_login->is_active = $request->status;
                $user_login->created_at = Carbon::now()->toDateTimeString();
                $user_login->save();
            }
            return response()->json([
                'status' => true,
                'message' => 'Data Berhasil di Simpan'
            ], 200);
        }
        else {
            //return redirect()->route('employee.index')->with('success','saved');
            return response()->json([
                'status' =>false,
                'errors' => $error
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        //
        $data = DB::select(' select nik, nama, alamat_kota_id, alamat_provinsi_id, get_provinsi_name(alamat_provinsi_id) AS nama_provinsi, get_kota_name(alamat_kota_id) AS nama_kota FROM karyawan ');
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
        $data['emp'] = Employee::find($id);
        $data['dd'] = Employee::all();
        $data['dd_prov'] = Provinsi::all();
        $data['dd_kota'] = Kota::all();
        $data['dd_level'] = Level::all();
        $data['dd_kantor'] = Kantor::all();
        $data['dd_divisi'] = Divisi::all();
        $data['dd_jabatan'] = Jabatan::all();
        $data['dd_beban'] = Beban::all();
        $data['dd_working_type'] = DB::select('select nama, id from working_type');
        $data['dd_ptkp'] = DB::select('select id, nama_ptkp from master_ptkp');
        return view('karyawan.edit',$data);
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
        $error = $request->validate([
            'nik' => 'required|regex:/^[0-9]+$/',
            'nama' => 'required',
            'email' => 'email',
            'alamat_ktp' => 'required',
            'provinsi_ktp' => 'required',
            'kota_ktp' => 'required',
            'alamat_domisili' => 'required',
            'provinsi_domisili' => 'required',
            'kota_domisili' => 'required',
            'npwp' => 'required|regex:/^[0-9.-]+$/',
            'bpjs' => 'required|regex:/^[0-9.-]+$/',
            'jenis_kelamin' => 'required',
            'beban_id' => 'required',
            'handphone' => 'required',
            'doj' => 'required',
            'foto' => 'mimes:jpeg,png,jpg,bmp,JPG,JPEG,PNG|max:5120'
        ]);

        if ($error) {
            $emp = Employee::find($id);

            $emp->nik = $request->nik;
            $emp->nama = $request->nama;
            $emp->alamat_ktp = $request->alamat_ktp;
            $emp->alamat_ktp_provinsi_id = $request->provinsi_ktp;
            $emp->alamat_ktp_kota_id = $request->kota_ktp;
            $emp->alamat = $request->alamat_domisili;
            $emp->alamat_kota_id = $request->kota_domisili;
            $emp->alamat_provinsi_id = $request->provinsi_domisili;

            $emp->npwp = $request->npwp;
            $emp->email = $request->email;
            $emp->agama = $request->agama;
            $emp->handphone = $request->handphone;
            $emp->keterangan = $request->keterangan;
            $emp->date_birth = $request->dob;
            $emp->date_joining = $request->doj;
            $emp->date_resign = $request->date_resign;
            $emp->working_type_id = $request->working_type;
            $emp->status_pernikahan = $request->status_pernikahan;
            $emp->ptkp = $request->ptkp;
            $emp->bpjs = $request->bpjs;
            $emp->jenis_kelamin = $request->jenis_kelamin;
            $emp->tempat_lahir = $request->tempat_lahir;

            $emp->level_id = $request->level;
            $emp->working_area = $request->working_area;
            $emp->beban_id = $request->beban_id;
            $emp->beban_id_kaskecil = $request->beban_id_kaskecil;
            $emp->divisi_id = $request->divisi_id;
            $emp->jabatan_id = $request->jabatan_id;
            $emp->is_pengurus = $request->is_pengurus;
            $emp->status = $request->status;

            // upload foto karyawan
            if ($files = $request->file('foto')) {
                Storage::delete('uploads/employees/'.$emp->foto);
                $destinationPath = 'uploads/employees/'; // upload path
                $profileImage = Str::slug($request->nik,'_') . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $profileImage);
                $emp->foto = $profileImage;
             }

            $emp->updated_by = Auth::id();
            $emp->updated_at = Carbon::now()->toDateTimeString();
            $save = $emp->save();

            if ($save) {
                // mendapatkan id terakhir dari karyawan yang baru di input, untuk mendapatkan id nya lalu di input sebagai id di table karyawan login
                // karyawan.id = karyawan_login.id
                $user_login = \App\KaryawanLoginModel::find($id);
                // user login mobile apps
                $user_login->nik_karyawan = $request->nik;
                $user_login->username = $request->handphone;
                if ($request->password) {
                    $user_login->password = Hash::make($request->password);
                }
                $user_login->is_active = $request->status;
                $user_login->created_at = Carbon::now()->toDateTimeString();
                $user_login->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui'
            ], 200);
        }
        else {
            //return redirect()->route('employee.index')->with('success','saved');
            return response()->json([
                'status' =>false,
                'errors' => $error
            ], 404);
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
        $emp = Employee::find($id);
        $emp->deleted_by = Auth::id();
        $emp->deleted_at = Carbon::now()->toDateTimeString();
        $emp->save();
        //$emp->delete();
        $user_login = \App\KaryawanLoginModel::find($id);
        $user_login->deleted_at =  Carbon::now()->toDateTimeString();
        $user_login->save();
        return response()->json([
            'success' => 'data berhasil di hapus',

        ]);
    }

    public function chained_prov_kot(Request $request){
       
        if ($request->prov) {
            return $data = DB::table('master_kota')
                ->where('provinsi_id', '=', $request->prov)
                ->get(['id','nama as text']);
        }
        else {
            return $data = DB::table('master_kota')
                ->where('id', '=', $request->kota)
                ->get(['provinsi_id']);
        }
    }
}
