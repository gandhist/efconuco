<?php

namespace App\Http\Controllers;
use App\Stock;
use App\StockHeader;
use App\TroHeader;
use App\OutStock;
use App\Employee;
use App\MasterGudang;
use App\MasterStock;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;

class StockController extends Controller
{
    //
    public function stock_info()
    {
        $data_gudang = DB::select("select id, nama, lantai from master_gudang where kategori = 'PANTRY' ");
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $string[] = "
            CASE
                WHEN 1=1 THEN 
                (SELECT a.balance
            FROM storeq_details  a
            INNER JOIN 
            (
            SELECT MAX(id) AS id
            FROM storeq_details 
            WHERE STATUS in ('PAID','TRANSFERED','OUT')
            GROUP BY id_barang, gudang_id
            ) b
            ON a.id = b.id
            WHERE a.id_barang = c.kode_barang
            AND a.gudang_id = $key->id)
            END AS
        ". $field_name;
        $field_name_html[] = $key->nama;
        $field_name_table[] = $field_name;
        }
        $data['field_name_html'] = $field_name_html;
        $data['field_name_table'] = $field_name_table;
        $data['stock'] = DB::select("
        SELECT c.id, c.kode_barang, c.nama, c.qty, c.qty_satuan, 
        ".implode(",",$string)."
        , c.harga
        FROM master_stock c
        WHERE c.kategori = 'PANTRY'
        ");
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        
        return view('stock.info', $data);
    }

    public function restock(Request $request)
    {
        
        if ($request->isMethod('GET')) {
            return redirect('stock/info')->with('success','Pilih terlebih dahulu item yang akan di Re-Stock');
        }

        // validasi work here
        $cek_status = DB::table('storeq_details')
                        ->whereRaw("status not in ('PAID','TRANSFERED','OUT') ")
                        ->whereIn('id_barang',$request->selectedId)->groupBy('id_header')->get(['id_header']);
       if ($cek_status->count() > 0) {
          foreach ($cek_status as $key) {
              $id_headers[] = $key->id_header;
          }
        //   return $id_headers;
          $pending = StockHeader::whereIn('id', $id_headers)->get(['trans_no','id']);
          foreach ($pending as $key) {
              $trans_no[] = $key->trans_no;
          }
          $trans_no = implode(", ",$trans_no);
          return redirect('stock/info')->with('success',"selesaikan terlebih dahulu transaksi atas nomor : $trans_no");
       }
        //end of validasi
        // running number
        $code = "/RES/STO/";
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');
        switch ($bulan) {
            case '1':
            $bulan = 'I';
            break;
            case '2':
            $bulan = 'II';
            break;
            case '3':
            $bulan = 'III';
            break;
            case '4':
            $bulan = 'IV';
            break;
            case '5':
            $bulan = 'V';
            break;
            case '6':
            $bulan = 'VI';
            break;
            case '7':
            $bulan = 'VII';
            break;
            case '8':
            $bulan = 'VIII';
            break;
            case '9':
            $bulan = 'IX';
            break;
            case '10':
            $bulan = 'X';
            break;
            case '11':
            $bulan = 'XI';
            break;
            case '12':
            $bulan = 'XII';
            break;
        }
        $rn_a = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
            $data['trans_no'] = sprintf('%04d', $rn[0]->rn+1).$code.$bulan."/".$tahun;
        }
        // end of running number
        $selected_id = $request->selectedId;
        $selected_id = implode("','",$selected_id);
        $data_gudang = DB::select("select id, nama, lantai from master_gudang where kategori = 'PANTRY' ");
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $string[] = "
            CASE
                WHEN 1=1 THEN 
                c.qty - (SELECT a.balance
            FROM storeq_details  a
            INNER JOIN 
            (
            SELECT MAX(id) AS id
            FROM storeq_details 
            WHERE STATUS = 'PAID'
            GROUP BY id_barang, gudang_id
            ) b
            ON a.id = b.id
            WHERE a.id_barang = c.kode_barang
            AND a.gudang_id = $key->id)
            END AS
        ". $field_name;
        $field_name_html[] = $key->nama;

        }
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $field_name_table['nm'] = $field_name;
            $field_name_table['id'] = str::snake($key->id);
            $inp[] = $field_name_table;
        }
        $data['field_name_html'] = $field_name_html;
        $data['field_name_table'] = $inp;
        $data['stock'] = DB::select("
        SELECT c.id, c.kode_barang, c.nama, c.qty, c.qty_satuan, 
        ".implode(",",$string)."
        , c.harga
        FROM master_stock c
        WHERE c.kategori = 'PANTRY' and c.kode_barang in ('$selected_id')
        ");
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['dd_vendor'] = Employee::all(); 
        
        return view('stock.restock', $data);
    }

    public function edit_restock($id)
    {
        $data['header'] = StockHeader::find($id);

      
        $data_gudang = DB::select("select id, nama, lantai from master_gudang where kategori = 'PANTRY' ");
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $string[] = "
            CASE
                WHEN 1=1 THEN (SELECT concat(id,'_',balance) FROM storeq_details WHERE id_header = $id
            AND deleted_at IS NULL AND status NOT IN ('TRANSFERED','OUT') AND gudang_id = $key->id AND id_barang = a.id_barang)
            END AS ". $field_name;
        $field_name_html[] = $key->nama;
        }
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $field_name_table['nm'] = $field_name;
            $field_name_table['id'] = str::snake($key->id);
            $inp[] = $field_name_table;
        }
        $data['field_name_html'] = $field_name_html;
        $data['field_name_table'] = $inp;
        $data['stock'] = DB::select("
        SELECT DISTINCT(a.id_barang) AS kode_barang, b.nama, b.qty, b.qty_satuan, 
        ".implode(",",$string)."
        ,b.harga
        FROM storeq_details a 
        INNER JOIN master_stock b 
        ON a.id_barang = b.kode_barang
        WHERE a.id_header = $id
        AND a.deleted_at IS NULL");
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['dd_vendor'] = Employee::all(); 
        return view('stock.edit_restock', $data);
    }
    
     public function edit_restock_after_paid($id)
    {
        $data['header'] = StockHeader::find($id);

      
        $data_gudang = DB::select("select id, nama, lantai from master_gudang where kategori = 'PANTRY' ");
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $string[] = "
            CASE
                WHEN 1=1 THEN (SELECT concat(id,'_',balance) FROM storeq_details WHERE id_header = $id
             AND status NOT IN ('PAID','TRANSFERED','OUT') AND gudang_id = $key->id AND id_barang = a.id_barang)
            END AS ". $field_name;
        $field_name_html[] = $key->nama;
        }
        foreach ($data_gudang as $key) {
            $field_name = str::snake($key->nama);
            $field_name_table['nm'] = $field_name;
            $field_name_table['id'] = str::snake($key->id);
            $inp[] = $field_name_table;
        }
        $data['field_name_html'] = $field_name_html;
        $data['field_name_table'] = $inp;
        $data['stock'] = DB::select("
        SELECT DISTINCT(a.id_barang) AS kode_barang, b.nama, b.qty, b.qty_satuan, 
        ".implode(",",$string)."
        ,b.harga
        FROM storeq_details a 
        INNER JOIN master_stock b 
        ON a.id_barang = b.kode_barang
        WHERE a.id_header = $id
        ");
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['dd_vendor'] = Employee::all(); 
        return view('stock.edit_restock', $data);
    }

    public function bulk_add(Request $request)
    {
        // return $request->all();
        // validasi here
        // jika ada purchase yang pending tidak bisa di process

        $gudang = MasterGudang::all();
        $master_barang = MasterStock::all();
        
        // cek perubahan data harga, lalu update harga ke master_stock
        foreach ($master_barang as $key) {
            $prefix_harga = "harga_input_".$key->kode_barang;
            if ($request->has($prefix_harga) ) {
                MasterStock::where('kode_barang', $key->kode_barang)
                        ->update([
                        'harga'=> $request->$prefix_harga,
                        'updated_by'=> Auth::id(),
                        'updated_at'=> Carbon::now()->toDateTimeString()
                        ]);
            }
        }

        // store ke header
        $header = new StockHeader;
        $header->trans_no = $request->trans_no;
        $header->date_create = $request->tanggal;
        $header->created_by = Auth::id();
        $header->reason = $request->reason;
        $header->total_price = $request->grand_totals;
        $name = str_replace('/','_',$request->trans_no);
         if ($files = $request->file('pr_files')) {
            $destinationPath = 'uploads/stock_man/invoice'; // upload path
            $profileImage = "PR_".$name . "." . $files->getClientOriginalExtension();
            $files->move($destinationPath, $profileImage);
            $header->pr_file = $profileImage;
         }
        $header->vendor_id = $request->vendor;
        $header->status = $request->status;
        if ($request->status == "CANCELED" || $request->status == "REJECTED") {
            $header->reason = $request->reason;
        }
        elseif ($request->status == "PAID") {
            $header->paid_date = $request->payment_date;
            if ($files = $request->file('bukti_bayars')) {
                $destinationPath = 'uploads/stock_man/bukti_bayar'; // upload path
                $profileImage = "BB_".$name . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $profileImage);
                $header->bukti_bayar = $profileImage;
             }
        }
        $save_header = $header->save();
        
        if ($save_header) {

            // save to details 
            foreach ($master_barang as $key) {
                foreach ($gudang as $store) {
                    $prefix = "i_".$key->kode_barang."_".$store->id;
                    if ($request->has($prefix)) {
                        $bal = Stock::where('id_barang',$key->kode_barang)->where('gudang_id',$store->id)->whereIn('status',['PAID','TRANSFERED','OUT'])->orderBy('id','desc')->first(['balance']);
                        if (is_null($bal) ) {
                            $bal = 0;
                        }
                        else {
                            $bal = $bal->balance;
                        }
                        $details = new Stock;
                        $details->id_header = $header->id;
                        $details->id_barang = $key->kode_barang;
                        $details->stock_in = $request->$prefix;
                        $details->balance = $request->$prefix + $bal;
                        $details->gudang_id = $store->id;
                        $details->status = $request->status;
                        $details->tanggal = $request->tanggal;
                        $price = "total_".$key->kode_barang;
                        if ($request->has($price)) {
                            $details->price = $request->$price;
                        }
                        $details->created_by = Auth::id();
                        $details->created_at = Carbon::now()->toDateTimeString();
                        $details->save();
                        //echo "id_barang".$key->kode_barang ." Qty :" . $request->$prefix." gudang id = ".$store->id."status = ". $request->status;
                    }
                }
            }
            // end of save to details

            // response success
            return response()->json([
                'status' => true,
                'message' => 'Stock berhasil di Process'
            ],200);
        }
        
        // perubahan status menggunakan trigger mysql atau menggunakan relasi ke table storeq_log
        // on insert affect to storeq_log by trigger and bulk to DONE
        // on update header affect to storeq_log DONE
        // details by php DONE
        // cek jika inputan lebih kecil dari saldo terakhir maka akan di warning, sebenarnya opbal nya adalah yang dari inputan user ketika restock
        // jika ada perubahan atau salah input maka akan di buatkan menu adjustment tambah atau kurang
        // di frontend validasi
        // tidak boleh minus
        // tidak bisa buat transaksi baru jika masih ada status selain paid, karena akan berpengaruh kepada saldo
        // ===============
        // details bisa ubah quantity dan harga jika status belum paid. DONE
        // di menu invoice jika status sudah paid maka hanya bisa melihat saja, ataias tombol show details saja, kalo selain itu bisa di update DONE

        // jika berhasil store ke header maka lanjut input ke details nya DONE
    }

    public function bulk_update(Request $request, $id)
    {
        // return $request->all();
        $gudang = MasterGudang::all();
        $master_barang = MasterStock::all();
        $storeq_details = Stock::where('id_header',$id)->get();
        
        // cek perubahan data harga, lalu update harga ke master_stock
        foreach ($master_barang as $key) {
            $prefix_harga = "harga_input_".$key->kode_barang;
            if ($request->has($prefix_harga) ) {
                MasterStock::where('kode_barang', $key->kode_barang)
                        ->update([
                        'harga'=> $request->$prefix_harga,
                        'updated_by'=> Auth::id(),
                        'updated_at'=> Carbon::now()->toDateTimeString()
                        ]);
            }
        }

        // update ke header
        $header = StockHeader::find($id);
        $header->trans_no = $request->trans_no;
        $header->date_create = $request->tanggal;
        $header->updated_by = Auth::id();
        $header->reason = $request->reason;
        $header->total_price = $request->grand_totals;
        // delete gambar
         if ($files = $request->file('pr_files')) {
            Storage::delete('uploads/stock_man/invoice'.$header->pr_file);
            $destinationPath = 'uploads/stock_man/invoice'; // upload path
            $profileImage = "PR_".$request->trans_no . "." . $files->getClientOriginalExtension();
            $files->move($destinationPath, $profileImage);
            $header->pr_file = $profileImage;
         }
        $header->vendor_id = $request->vendor;
        $header->status = $request->status;
        if ($request->status == "CANCELED" || $request->status == "REJECTED") {
            $header->reason = $request->reason;
        }
        elseif ($request->status == "PAID") {
            $header->paid_date = $request->payment_date;
            if ($files = $request->file('bukti_bayars')) {
                Storage::delete('uploads/stock_man/bukti_bayar'.$header->bukti_bayar);
                $destinationPath = 'uploads/stock_man/bukti_bayar'; // upload path
                $profileImage = "BB_".$request->trans_no . "." . $files->getClientOriginalExtension();
                $files->move($destinationPath, $profileImage);
                $header->bukti_bayar = $profileImage;
             }
        }
        $save_header = $header->save();
        // jika berhasil save
        if ($save_header) {
                    // save to details 
        foreach ($master_barang as $key) {
            foreach ($gudang as $store) {
                foreach ($storeq_details as $storeq_det) {
                    $prefix = "i_".$key->kode_barang."_".$store->id."_".$storeq_det->id;
                    if ($request->has($prefix)) {
                        $bal = Stock::where('id_barang',$key->kode_barang)->where('gudang_id',$store->id)->whereIn('status',['PAID','TRANSFERED','OUT'])->orderBy('id','desc')->first(['balance']);
                        if (is_null($bal) ) {
                            $bal = 0;
                        }
                        else {
                            $bal = $bal->balance;
                        }
                        // jika paid 
                        if ($request->status == "PAID") {
                        $details_delete = Stock::find($storeq_det->id);
                        $details_delete->deleted_by = Auth::id();
                        $details_delete->deleted_at = Carbon::now()->toDateTimeString();
                        $details_delete->save();
                        // di delete data lama agar ketika update stock terbaru mendapatkan id terbaru karena pencarian stock terupdate berdasarkan id terbesar
                        $details = new Stock;
                        $details->id_header = $header->id;
                        $details->id_barang = $key->kode_barang;
                        $details->tanggal = $request->tanggal;
                        $details->stock_in = $request->$prefix;
                        $details->balance = $request->$prefix + $bal;
                        $details->gudang_id = $store->id;
                        $details->status = $request->status;
                        $price = "total_".$key->kode_barang;
                        if ($request->has($price)) {
                            $details->price = $request->$price;
                        }
                        $details->updated_by = Auth::id();
                        $details->updated_at = Carbon::now()->toDateTimeString();
                        $details->save();
                        }
                        // jika tidak paid 
                        else {
                            $details = Stock::find($storeq_det->id);
                            $details->id_header = $header->id;
                            $details->id_barang = $key->kode_barang;
                            $details->tanggal = $request->tanggal;
                            $details->stock_in = $request->$prefix;
                            $details->balance = $request->$prefix;
                            $details->gudang_id = $store->id;
                            $details->status = $request->status;
                            $price = "total_".$key->kode_barang;
                            if ($request->has($price)) {
                                $details->price = $request->$price;
                            }
                            $details->updated_by = Auth::id();
                            $details->updated_at = Carbon::now()->toDateTimeString();
                            $details->save();
                        }
                        
                        // echo "id_barang".$key->kode_barang ." Qty :" . $request->$prefix." gudang id = ".$store->id."status = ". $request->status." ID ROW: ".$storeq_det->id;
                    } // end if
                } // end foreach storeq_details
            } // end foreach gudang
        } // end foreach master_barang
        // end of save to details

            // response success
            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil di perbarui, Saldo Sudah bertambah'
            ],200);
        }




    }

    public function purchase()
    {
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['payment_method'] = DB::table('master_payment_method')->get();
        $data['trans_type'] = DB::table('master_transaction_type')->get();
        $data['vehicle'] = DB::table('master_vehicle')->get();
        return view('stock.purchasing.index', $data);
    }

    public function pr_list(Request $request)
    {
        // form filter
        $trans_no = $request->input('trans_no');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');
        $vendor = $request->input('vendor');
        $status = $request->input('status');
        $paid_date_start = $request->input('filter_paid_date_start');
        $paid_date_end = $request->input('filter_paid_date_end');

        // definisi orderable column
        $columns = array(
            0 => 'a.id',
            1 => 'a.trans_no',
            2 => 'a.tanggal',
            3 => 'b.vendor_id',
            4 => 'a.pr_file',
            5 => 'a.paid_date',
            6 => 'a.bukti_bayar',
            7 => 'a.total_price',
            8 => 'a.reason',
        );

        $totalData = StockHeader::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            // $dataAbsen = KaryawanAbsensi::offset($start)
            //                     ->limit($limit)
            //                     ->orderBy($order, $dir)
            //                     ->get();
            $dataPr = DB::select("
            SELECT a.id, a.trans_no, a.date_create, b.nama, a.status, a.pr_file, a.bukti_bayar, a.paid_date, a.total_price, a.reason FROM pantry_storeq_header a
            INNER JOIN master_vendor b ON a.vendor_id = b.id WHERE a.deleted_at is NULL
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataPr = DB::select("
            SELECT a.id, a.trans_no, a.date_create, b.nama, a.status, a.pr_file, a.bukti_bayar, a.paid_date, a.total_price, a.reason FROM pantry_storeq_header a
            INNER JOIN master_vendor b ON a.vendor_id = b.id WHERE a.deleted_at is NULL
            and a.reason like '%$search%'
            or 
            a.trans_no like '%$search%'
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            // array ketika terjadi pencarian maka akan count data yang di cari, karena menggunakan raw query maka data yang tampil berupa array
            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM pantry_storeq_header a
            INNER JOIN master_vendor b ON a.vendor_id = b.id WHERE a.deleted_at is NULL
            and a.reason like '%$search%'
            or 
            a.trans_no like '%$search%'
            ")[0]->filtered;
        }

         // custom filter query here
         if (!empty($trans_no) || !empty($start_date) || !empty($end_date) || !empty($vendor) || !empty($status) || !empty($paid_date_start) || !empty($paid_date_end) ) {
            $search = $request->input('search.value');
            $trans_no = (!empty($trans_no)) ? "and a.trans_no = '$trans_no'" : '' ;
            if (!empty($start_date) && !empty($end_date) ) {
                $period = " and a.date_create between '$start_date' and '$end_date' ";
            }
            else {
                $period = '';
            }
            $vendor = (!empty($vendor)) ? "and a.vendor_id = '$vendor'" : '' ;
            $status = (!empty($status)) ? "and a.status = '$status'" : '' ;
            if (!empty($paid_date_start) && !empty($paid_date_end) ) {
                $paid_date = " and a.paid_date between '$start_date' and '$end_date' ";
            }
            else {
                $paid_date = '';
            }

            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataPr = DB::select("
            SELECT a.id, a.trans_no, a.date_create, b.nama, a.status, a.pr_file, a.bukti_bayar, a.paid_date, a.total_price, a.reason FROM pantry_storeq_header a
            INNER JOIN master_vendor b ON a.vendor_id = b.id WHERE a.deleted_at is NULL
            $trans_no $period $vendor $status $paid_date
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM pantry_storeq_header a
            INNER JOIN master_vendor b ON a.vendor_id = b.id WHERE a.deleted_at is NULL
            $trans_no $period $vendor $status $paid_date
            ")[0]->filtered;
        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataPr)) {
            foreach ($dataPr as $ro) {
                $edit = url('stock/restock', $ro->id);
                $edit_paid = url('stock/restock_after_paid', $ro->id);
                $delete = $ro->id;

                $row['bulkDelete'] = "<input type='checkbox' name='deleteAll[]' onclick='partialSelected()' class='bulkDelete' id='bulkDeleteName' value='$ro->id'>";
                $row['no'] = $no;
                $row['trans_no'] = $ro->trans_no;
                $row['tanggal'] = $ro->date_create;
                $row['nama'] = $ro->nama;
                $row['status'] = $ro->status;
                $row['options'] = "
                <a class='btn btn-xs btn-warning' href='$edit'><span class='glyphicon glyphicon-pencil'></span></a>
                <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
                ";
                if ($row['status'] == 'APPROVED') {
                    $row['status'] = "<span class='label label-primary'> $ro->status </span>";
                }
                else if($row['status'] == 'PROCESSED'){
                    $row['status'] = "<span class='label label-info'> $ro->status </span>";
                }
                else if($row['status'] == 'CANCELED'){
                    $row['status'] = "<span class='label label-warning'> $ro->status </span>";
                }
                else if($row['status'] == 'REJECTED'){
                    $row['status'] = "<span class='label label-danger'> $ro->status </span>";
                }
                else {
                    $row['status'] = "<span class='label label-success'> $ro->status </span>";
                    $row['options'] = "
                <a class='btn btn-xs btn-warning' href='$edit_paid'><span class='glyphicon glyphicon-pencil'></span></a>
                ";
                }
                $row['pr_file'] = "<a href='". asset('uploads/stock_man/invoice/') ."/".$ro->pr_file."' target='_blank'>". $ro->pr_file ."</a> ";
                $row['paid_date'] = $ro->paid_date;
                $row['bukti_bayar'] = "<a href='". asset('uploads/stock_man/bukti_bayar/') ."/".$ro->bukti_bayar."' target='_blank'>". $ro->bukti_bayar ."</a> ";
                $row['total'] = "Rp " . number_format($ro->total_price,0,',','.');
                $row['reason'] = $ro->reason;
                
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

    public function tr_stock()
    {
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['dd_item'] = MasterStock::where('kategori','PANTRY')->get();
        $data['dd_gudang'] = MasterGudang::where('kategori','PANTRY')->get();
        // running number
        $code = "/TRO/STO/";
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');
        switch ($bulan) {
            case '1':
            $bulan = 'I';
            break;
            case '2':
            $bulan = 'II';
            break;
            case '3':
            $bulan = 'III';
            break;
            case '4':
            $bulan = 'IV';
            break;
            case '5':
            $bulan = 'V';
            break;
            case '6':
            $bulan = 'VI';
            break;
            case '7':
            $bulan = 'VII';
            break;
            case '8':
            $bulan = 'VIII';
            break;
            case '9':
            $bulan = 'IX';
            break;
            case '10':
            $bulan = 'X';
            break;
            case '11':
            $bulan = 'XI';
            break;
            case '12':
            $bulan = 'XII';
            break;
        }
        $rn_a = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
            $data['trans_no'] = sprintf('%04d', $rn[0]->rn+1).$code.$bulan."/".$tahun;
        }
        // end of running number
        return view('stock.transfer.index',$data);
    }

    public function tro_add(Request $request)
    {
        //return $request->all();
        // validasi work here
    //     $cek_status = DB::table('storeq_details')
    //                     ->whereRaw("status not in ('PAID','TRANSFERED','OUT') ")
    //                     ->where('id_barang',$request->id_barang)->groupBy('id_header')->get(['id_header']);
    //    if ($cek_status->count() > 0) {
    //       foreach ($cek_status as $key) {
    //           $id_headers[] = $key->id_header;
    //       }
    //       $pending = StockHeader::whereIn('id', $id_headers)->get(['trans_no','id']);
    //       foreach ($pending as $key) {
    //           $trans_no[] = $key->trans_no;
    //       }
    //       $trans_no = implode(", ",$trans_no);
    //       return response()->json([
    //           'status' => false,
    //           'message' => "selesaikan terlebih dahulu transaksi atas nomor : $trans_no"
    //       ], 200);
    //    }
        //end of validasi 

        $header = new TroHeader;
        $header->trans_no = $request->trans_no;
        $header->id_barang = $request->id_barang;
        $header->tanggal = $request->tanggal;
        $header->qty = $request->qty;
        $header->id_gudang_asal = $request->asal;
        $header->id_gudang_tujuan = $request->tujuan;
        $header->saldo_gudang_asal = $request->qty_asal;
        $header->saldo_gudang_tujuan = $request->qty_tujuan;
        $header->remarks = $request->keterangan;
        $simpan_header = $header->save();
        if ($simpan_header) {
            $bal_asal = Stock::where('id_barang',$request->id_barang)->where('gudang_id',$request->asal)->whereIn('status',['PAID','TRANSFERED','OUT'])->orderBy('id','desc')->first(['balance']);
            // asal
            $detail_asal = new Stock;
            $detail_asal->id_header = $header->id;
            $detail_asal->id_barang = $request->id_barang;
            $detail_asal->stock_out = $request->qty;
            $detail_asal->gudang_id = $request->asal;
            $detail_asal->tanggal = $request->tanggal;
            $detail_asal->status = "TRANSFERED";
            $detail_asal->balance = $bal_asal->balance - $request->qty;
            $detail_asal->created_by = Auth::id();
            $detail_asal->created_at = Carbon::now()->toDateTimeString();
            $detail_asal->save();
            // tujuan
            $bal_tujuan = Stock::where('id_barang',$request->id_barang)->where('gudang_id',$request->tujuan)->whereIn('status',['PAID','TRANSFERED','OUT'])->orderBy('id','desc')->first(['balance']);
            $detail_tujuan = new Stock;
            $detail_tujuan->id_header = $header->id;
            $detail_tujuan->id_barang = $request->id_barang;
            $detail_tujuan->stock_in = $request->qty;
            $detail_tujuan->tanggal = $request->tanggal;
            $detail_tujuan->gudang_id = $request->tujuan;
            $detail_tujuan->status = "TRANSFERED";
            $detail_tujuan->balance = $request->qty + $bal_tujuan->balance;
            $detail_tujuan->created_by = Auth::id();
            $detail_tujuan->created_at = Carbon::now()->toDateTimeString();
            $detail_tujuan->save();

            return response()->json([
                'status' => true,
                'message' => 'Transfer berhasil di lakukan'
            ], 200);
        }
    }

    public function get_balance($id_barang, $id_gudang){
        $bal = Stock::where('id_barang',$id_barang)->where('gudang_id',$id_gudang)->whereIn('status',['PAID','TRANSFERED','OUT'])->orderBy('id','desc')->first(['balance']);
        return $bal;
    }

    public function chained_gudang(Request $request)
    {
        if ($request->asal) {
            return $data = DB::table('master_gudang')
            ->where('id', '!=', $request->asal)->where('kategori','PANTRY')
                ->get(['id','nama as text']);
        }
        else {
            return $data = DB::table('master_gudang')
                ->where('id', '!=', $request->tujuan)->where('kategori','PANTRY')
                ->get(['id','nama as text']);
        }
    }

    // list json datatables
    public function TroHeaderList(Request $request)
    {
        // form filter
        $nik = $request->input('filter_nik');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');
        $type = $request->input('filter_type');
        $payment = $request->input('filter_payment');
        $dekre = $request->input('filter_dekre');
        $vehicle = $request->input('filter_vehicle');

        // definisi orderable column
        $columns = array(
            0 => 'id',
            1 => 'trans_no',
            2 => 'id_barang',
            3 => 'id_gudang_asal',
            4 => 'id_gudang_asal',
            5 => 'id_gudang_asal',
            6 => 'id_gudang_asal',
            7 => 'id_gudang_asal'
        );

        $totalData = TroHeader::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            $dataTransfer = TroHeader::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            or a.trans_no like '%$search%'
            and a.deleted_at is null
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            // array ketika terjadi pencarian maka akan count data yang di cari, karena menggunakan raw query maka data yang tampil berupa array
            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            or a.trans_no like '%$search%'
            and a.deleted_at is null
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($start_date) || !empty($end_date) || !empty($payment) || !empty($type) || !empty($dekre) || !empty($vehicle) ) {
            $search = $request->input('search.value');
            $nik = (!empty($nik)) ? "and a.karyawan_id = '$nik'" : '' ;
            if (!empty($start_date) && !empty($end_date) ) {
                $period = " and a.tanggal between '$start_date' and '$end_date' ";
            }
            else {
                $period = '';
            }
            $type = (!empty($type)) ? "and a.transaction_type_id = '$type'" : '' ;
            $payment = (!empty($payment)) ? "and a.payment_method_id = '$payment'" : '' ;
            if ($dekre == 'kredit') {
                $dekre = (!empty($dekre)) ? "and a.credit is not null" : '' ;
            }
            else {
                $dekre = (!empty($dekre)) ? "and a.debit is not null" : '' ;
            }
            $vehicle = (!empty($vehicle)) ? "and a.vehicle_id = '$vehicle'" : '' ;

            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, a.karyawan_id, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            $nik
            $period $type $payment $dekre $vehicle
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            $nik
            $period $type $payment $dekre $vehicle
            ")[0]->filtered;
           

        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataTransfer)) {
            foreach ($dataTransfer as $ro) {
                $row['no'] = $no;
                $row['trans_no'] = $ro->trans_no;
                $row['barang'] = $ro->master_barang->nama;
                $row['tanggal'] = $ro->tanggal;
                $row['qty'] = $ro->qty;
                $row['gudang_asal'] = $ro->id_gudang_asal;
                $row['gudang_tujuan'] = $ro->id_gudang_tujuan;
                $row['saldo_asal'] = $ro->saldo_gudang_asal;
                $row['saldo_tujuan'] = $ro->saldo_gudang_tujuan;
                $row['keterangan'] = $ro->remarks;
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

    public function io_stock()
    {
        $data['emp'] = Employee::all();
        $data['dd'] = Employee::all();
        $data['dd_item'] = MasterStock::where('kategori','PANTRY')->get();
        $data['dd_gudang'] = MasterGudang::where('kategori','PANTRY')->get();
        // running number
        $code = "/OUT/STO/";
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('Y');
        switch ($bulan) {
            case '1':
            $bulan = 'I';
            break;
            case '2':
            $bulan = 'II';
            break;
            case '3':
            $bulan = 'III';
            break;
            case '4':
            $bulan = 'IV';
            break;
            case '5':
            $bulan = 'V';
            break;
            case '6':
            $bulan = 'VI';
            break;
            case '7':
            $bulan = 'VII';
            break;
            case '8':
            $bulan = 'VIII';
            break;
            case '9':
            $bulan = 'IX';
            break;
            case '10':
            $bulan = 'X';
            break;
            case '11':
            $bulan = 'XI';
            break;
            case '12':
            $bulan = 'XII';
            break;
        }
        $rn_a = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
        if ($rn_a) {
            $rn = DB::table('running_number')->where('code',$code)->get(['rn','is_new_number']);
            $data['trans_no'] = sprintf('%04d', $rn[0]->rn+1).$code.$bulan."/".$tahun;
        }
        // end of running number
        return view('stock.out_stock.index', $data);
    }

    public function out_add(Request $request)
    {
        $header = new OutStock;
        $header->trans_no = $request->trans_no;
        $header->id_barang = $request->id_barang;
        $header->tanggal = $request->tanggal;
        $header->id_gudang = $request->asal;
        $header->qty = $request->qty;
        $header->saldo_terakhir = $request->qty_asal;
        $header->remarks = $request->keterangan;
        $header->created_by = Auth::id();
        $header->created_at = Carbon::now()->toDateTimeString();
        $simpan_header = $header->save();
        if ($simpan_header) {
            // asal
            $detail_asal = new Stock;
            $detail_asal->id_header = $header->id;
            $detail_asal->id_barang = $request->id_barang;
            $detail_asal->stock_out = $request->qty_asal_current;
            $detail_asal->gudang_id = $request->asal;
            $detail_asal->tanggal = $request->tanggal;
            $detail_asal->status = "OUT";
            $detail_asal->balance = $request->qty;
            $detail_asal->created_by = Auth::id();
            $detail_asal->created_at = Carbon::now()->toDateTimeString();
            $detail_asal->save();

            return response()->json([
                'status' => true,
                'message' => 'update stock berhasil di lakukan'
            ], 200);
        }
    }

    // list header out stock
    public function outStockList(Request $request)
    {
        // form filter
        $nik = $request->input('filter_nik');
        $start_date = $request->input('filter_start_date');
        $end_date = $request->input('filter_end_date');
        $type = $request->input('filter_type');
        $payment = $request->input('filter_payment');
        $dekre = $request->input('filter_dekre');
        $vehicle = $request->input('filter_vehicle');

        // definisi orderable column
        $columns = array(
            0 => 'id',
            1 => 'trans_no',
            2 => 'id_barang',
            3 => 'id_gudang_asal',
            4 => 'id_gudang_asal',
            5 => 'id_gudang_asal',
            6 => 'id_gudang_asal',
            7 => 'id_gudang_asal'
        );

        $totalData = OutStock::count();
        $totalFiltered = $totalData;

        $limit = $request->length;
        $start = $request->start;
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        // jika tidak ada get pada pencarian 
        if (empty($request->input('search.value'))) {
            $dataOutStock = OutStock::offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        } else {
            $search = $request->input('search.value');
            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            or a.trans_no like '%$search%'
            and a.deleted_at is null
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            // array ketika terjadi pencarian maka akan count data yang di cari, karena menggunakan raw query maka data yang tampil berupa array
            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.karyawan_id like '%$search%'
            or 
            b.nama like '%$search%'
            or a.trans_no like '%$search%'
            and a.deleted_at is null
            ")[0]->filtered;
        }

        // custom filter query here
        if (!empty($nik) || !empty($start_date) || !empty($end_date) || !empty($payment) || !empty($type) || !empty($dekre) || !empty($vehicle) ) {
            $search = $request->input('search.value');
            $nik = (!empty($nik)) ? "and a.karyawan_id = '$nik'" : '' ;
            if (!empty($start_date) && !empty($end_date) ) {
                $period = " and a.tanggal between '$start_date' and '$end_date' ";
            }
            else {
                $period = '';
            }
            $type = (!empty($type)) ? "and a.transaction_type_id = '$type'" : '' ;
            $payment = (!empty($payment)) ? "and a.payment_method_id = '$payment'" : '' ;
            if ($dekre == 'kredit') {
                $dekre = (!empty($dekre)) ? "and a.credit is not null" : '' ;
            }
            else {
                $dekre = (!empty($dekre)) ? "and a.debit is not null" : '' ;
            }
            $vehicle = (!empty($vehicle)) ? "and a.vehicle_id = '$vehicle'" : '' ;

            // definisikan parameter pencarian disini dengan kondisi orwhere
            $dataAbsen = DB::select("
            SELECT a.id, a.trans_no, a.karyawan_id, b.nik, b.nama, a.tanggal, c.name AS type_trans, a.transaction_desc, d.name AS payment_method, a.debit, a.credit, a.balance, a.file
            FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            $nik
            $period $type $payment $dekre $vehicle
            ORDER BY $order  $dir
            LIMIT $limit OFFSET $start
            ");

            $totalFiltered = DB::select("
            SELECT COUNT(b.nama) as filtered FROM kas_kecil_transaction a
            INNER JOIN karyawan b
            ON a.karyawan_id = b.id
            INNER JOIN master_transaction_type c 
            ON a.transaction_type_id = c.id
            INNER JOIN master_payment_method d
            ON a.payment_method_id = d.id
            where a.deleted_at is null
            $nik
            $period $type $payment $dekre $vehicle
            ")[0]->filtered;
           

        }

        //collection data here
        $data = array();
        $no = 1;
        if (!empty($dataOutStock)) {
            foreach ($dataOutStock as $ro) {
                $last_id = Stock::where('id_barang', $ro->id_barang)->where('gudang_id',$ro->id_gudang)->orderBy('id', 'desc')->first()->id;
                $id_header = Stock::where('id_barang', $ro->id_barang)->where('gudang_id',$ro->id_gudang)->where('id_header',  $ro->id)->first()->id;
                $delete = $ro->id;
                $row['no'] = $no;
                $row['trans_no'] = $ro->trans_no;
                $row['barang'] = $ro->master_barang->nama;
                $row['tanggal'] = $ro->tanggal;
                $row['gudang'] = $ro->id_gudang;
                $row['qty'] = $ro->qty;
                $row['saldo_akhir'] = $ro->saldo_terakhir;
                $row['keterangan'] = $ro->remarks;
                if ($id_header == $last_id) {
                    $row['options'] = "
                    <button class='btn btn-xs btn-warning' onclick='edit($ro->id)'><span class='glyphicon glyphicon-pencil'></span></button>
                    <button class='btn btn-xs btn-danger delete' data-id='$delete'><span class='glyphicon glyphicon-trash'></span></button>
                    ";
                }
                else {
                    $row['options'] = "
                    <button class='btn btn-xs btn-primary' onclick='show($ro->id)' ><span class='fa fa-eye'></span> Show</button>
                    ";
                }
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

    public function out_edit($id)
    {
        $data = OutStock::find($id);
        return $data;
    }
}
