@extends('templates.header')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Show Transaksi Pembelian
        {{-- <small>it all starts here</small>  --}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i>Home</a></li>
        <li><a href="#"><i class="fa fa-dashboard"></i>Pembelian</a></li>
        <li class="active"><a href="#">Edit Transaksi</a></li>
    </ol>

</section>

<!-- Main content -->
<section class="content">

    <!-- Default box -->
    <div class="box">
        <div class="box-body">

            {{-- sub menu  --}}
            {{-- end of sub menu  --}}

            {{-- table data of karyawanleave  --}}
            @if(session('status'))
            <div class="alert alert-success alert-dismissible fade in"> {{ session('status') }}
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            </div>
            @endif
            <div class="box-body">
            <!-- bilah kiri -->
            <div class="col-xs-4">
                <form role="form" enctype="multipart/form-data" name="formAdd" id="formAdd" method="post" action="{{ url("servicevehicle/store") }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="trans_no">PR Number</label>
                        <input readonly name="trans_no" type="text" class="form-control" id="trans_no" value="{{ $pr_header->pr_number }}" style="width: 100%;">
                        {{-- <span class="help-block" >{{ $errors->first('trans_no') }} </span> --}}
                    </div>
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="text" class="form-control" autocomplete="off" data-provide="datepicker" data-date-format="yyyy/mm/dd" name="tanggal" id="tanggal" placeholder="yyyy/mm/dd" value="{{ $pr_header->tanggal }}">
                        <span id="tanggal" class="help-block" >{{ $errors->first('tanggal') }} </span>
                    </div>
                    <div class="form-group">
                        <label for="nsfp">NSFP</label>
                        <input type="text" class="form-control" id="nsfp" name="nsfp" value="{{ $pr_header->nsfp }}" placeholder="nsfp">
                        <span id="nsfp" class="help-block" > {{ $errors->first('nsfp') }} </span>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="invoice_number">Invoice Number</label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ $pr_header->invoice_number }}">
                                <span id="invoice_number" class="help-block" > {{ $errors->first('invoice_number') }} </span>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="po_number">PO Number</label>
                                <input type="text" class="form-control" id="po_number" name="po_number" value="{{ $pr_header->po_number }}">
                                <span id="po_number" class="help-block" > {{ $errors->first('po_number') }} </span>
                            </div>
                        </div>
                    </div>
                    
                </div>
                    <!-- end off bilah tengah -->
                    <div class="col-xs-4">
                    <div class="row">
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label for="next_service">Kurs</label>
                                <select class="form-control select2" name="kurs" id="kurs" required style="width: 100%;">
                                    <option {{ $pr_header->kurs_kode == "IDR" ? 'selected' : '' }} value="IDR">IDR</option>
                                    <option {{ $pr_header->kurs_kode == "USD" ? 'selected' : '' }} value="USD">USD</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-8">
                            <div class="form-group">
                                <label for="nilai_kurs">Nilai Kurs</label>
                                <input type="number" class="form-control" id="nilai_kurs" name="nilai_kurs" value="{{ $pr_header->nilai_kurs }}" >
                                <span id="nilai_kurs" class="help-block" > {{ $errors->first('nilai_kurs') }} </span>
                            </div>
                        </div>
                    </div>
                    <div class="row" >
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control select2" name="status" disabled>
                                    <option {{ $pr_header->status == "APPROVED" ? 'selected' : '' }} value="APPROVED">APPROVED</option>
                                    <option {{ $pr_header->status == "REJECTED" ? 'selected' : '' }} value="REJECTED">REJECTED</option>
                                    <option {{ $pr_header->status == "CANCELED" ? 'selected' : '' }} value="CANCELED">CANCELED</option>
                                    <option {{ $pr_header->status == "PAID" ? 'selected' : '' }} value="PAID">PAID</option>
                                </select>
                                <span class="help-block" >{{ $errors->first('status') }} </span>
                            </div>
                        </div>
                        <div class="col-xs-8">
                            <div class="form-group">
                                <label>Vendor</label>
                                <select class="form-control select2" name="vendor_id" id="vendor_id">
                                    @foreach($vendor as $row)
                                    <option {{ $pr_header->vendor_id == $row->id ? 'selected' : '' }} value="{{ $row->id }}">{{ $row->nama }}</option>
                                    @endforeach
                                </select>
                                <span class="help-block" >{{ $errors->first('vendor_id') }} </span>
                            </div>
                        </div>
                    </div>
                    

                    <div class="form-group">
                        <label for="file">Bukti Invoice</label> <br>
                        @if($pr_header->file)
                        <a href="{{ url('uploads/pr/').$pr_header->file }}"> Lihat Lampiran</a>
                        @endif

                        <input type="file" id="file" name="file">
                        {{-- <p class="help-block">Tambahkam Foto untuk keterangan lebih rinci .</p> --}}
                    </div>

                    <div class="form-group" id="ket_div">
                        <label for="desc">Remarks</label>
                        <textarea name="desc" text="text" class="form-control" id="desc" value="{{old("desc")}}" placeholder="Keterangan">{{ $pr_header->remarks }}</textarea>
                        <span id="desc" class="help-block" > {{ $errors->first('desc') }} </span>
                    </div>
                </div>
                    <!-- end off bilah kanan -->
                    <div class="col-xs-4">
                    <div class="form-group">
                        <label for="tanggal_bayar">Tanggal Pembayaran</label>
                        <input type="text" class="form-control" autocomplete="off" data-provide="datepicker" data-date-format="yyyy/mm/dd" name="tanggal_bayar" id="tanggal_bayar" placeholder="Masukkan Date Start (yyyy/mm/dd)" value="{{ $pr_header->paid_date }}" required>
                        {{-- <span class="help-block" >{{ $errors->first('tanggal_bayar') }} </span> --}}
                    </div> 
                    <div class="form-group">
                        <label for="bukti_bayar">Bukti Pembayaran</label> <br>
                        @if($pr_header->paid_date_file)
                        <a href="{{ url('uploads/pr/').$pr_header->paid_date_file }}"> Lihat Lampiran</a>
                        @endif
                        <input type="file" id="bukti_bayar" name="bukti_bayar">
                        {{-- <p class="help-block">Tambahkam Foto u]ntuk keterangan lebih rinci .</p> --}}
                    </div>
                    
                    </div>
                </div>
                    <!-- /.box-body -->
                
                <div class="input_fields_wrap">

                    <div class="box-footer">
                            <a href="{{url("purchase")}}" class="btn btn-default">Cancel</a>
                            <button type="button" id="submit" name="submit" disabled onclick="store()" class="btn btn-primary">Update Data</button>
                    </div>
                </form>
                
<form name="items_list" id="items_list">
    <div>
        <table class="table" id="data-table">
            <thead>
                <tr>
                    <th scope="col" >No.</th>
                    <th scope="col" >Nama Barang</th>
                    <th scope="col" >Deskripsi</th>
                    <th scope="col" >Qty</th>
                    <th scope="col" >Qty Satuan</th>
                    <th scope="col" >Harga Satuan</th>
                    <th scope="col" >PPN (10%)</th>
                    <th scope="col" >Harga Total</th>
                    <th scope="col" >Action</th>
                    <th scope="col" ><button id='addrow' type='button' class="btn btn-primary"> <span class="fa fa-plus" ></span></button></th>
                </tr>
            </thead>
            <tbody>
                <?php  $last_iteration = 1;  ?>
                @foreach ($pr_details as $item)
                <tr class="tr_item">
                    <td scope="col">{{ $loop->iteration }}</td>
                    <input type="hidden" class="item_ls form-control" value="{{ $item->id }}" name="item_id_{{ $loop->iteration }}" id="item_id_update_{{ $loop->iteration }}"> 
                            {{-- pembeda ketika di controller item_id_update ini akan mengupdate, jika tambah baru maka dia akan create data baru --}}
                    <td>
                                            <select class="item_ls select2 form-control" name="item_name_{{ $loop->iteration }}" id="item_name_{{ $loop->iteration }}" required style="width: 100%;">
                                                @foreach($master_baja as $kry)
                                                <option {{ $item->id_barang == $kry->id ? 'selected' : '' }} value="{{ $kry->id }}">{{ $kry->nama }}</option>
                                                @endforeach
                                            </select>
                    </td>
                    <td>
                                <input class="item_ls form-control" id="item_desc_{{ $loop->iteration }}" name="item_desc_{{ $loop->iteration }}" value="{{ $item->desc }}">
                                <span id="item_desc_{{ $loop->iteration }}" class="help-block" > {{ $errors->first("item_desc_".$loop->iteration) }} </span>
                    </td>
                    <td>
                                <input type="number" data-id='{{ $loop->iteration }}' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') )" id="qty_item_{{ $loop->iteration }}" name="qty_item_{{ $loop->iteration }}" value="{{ $item->qty }}">
                                <span id="qty_item_{{ $loop->iteration }}" class="help-block" > {{ $errors->first("qty_item_".$loop->iteration) }} </span>
                    </td>
                    <td>
                                            <select class="item_ls select2 form-control" name="qty_satuan_item_{{ $loop->iteration }}" id="qty_satuan_item_{{ $loop->iteration }}" required style="width: 100%;">
                                                @foreach($uom as $kry)
                                                <option {{ $item->qty_uom == $kry->nama ? 'selected' : '' }} value="{{ $kry->nama }}">{{ $kry->nama }}</option>
                                                @endforeach
                                            </select>
                    </td>
                    <td>
                                <input type="number" data-id='{{ $loop->iteration }}' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') )" id="harga_satuan_item_{{ $loop->iteration }}" name="harga_satuan_item_{{ $loop->iteration }}" value="{{ $item->qty_price }}">
                                <span id="harga_satuan_item_{{ $loop->iteration }}" class="help-block" > {{ $errors->first('harga_satuan_item_'.$loop->iteration) }} </span>
        
                    </td>
                    <td>
                                <input type="number" data-id='{{ $loop->iteration }}' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') )" id="ppn_item_{{ $loop->iteration }}" name="ppn_item_{{ $loop->iteration }}" value="{{ $item->ppn }}">
                                <span id="ppn_item_{{ $loop->iteration }}" class="help-block" > {{ $errors->first('ppn_item_'.$loop->iteration) }} </span>
        
                    </td>
                    <td>
                                <input readonly class="total form-control" type='hidden' id="harga_total_item_{{ $loop->iteration }}" name="harga_total_item_{{ $loop->iteration }}" value="{{ $item->sub_total }}">
                                <p id="harga_total_item_show_{{ $loop->iteration }}">{{ "Rp. " . number_format($item->sub_total,2,',','.')  }}</p>
                    </td>
                    <td>
                                <button type='button' onclick="$(this).closest('tr').remove(); removeItem();" class="btn btn-danger btn-sm" ><span class="fa fa-trash" ></span></button> 
                    </td>
                </tr>
                <?php  $last_iteration = $loop->iteration +1;  ?>
              
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                                <td colspan='7' align='right' >Total</td>
                             <td ><b><i><input readonly type="hidden" class="form-control" id='grand_total' value="{{ $pr_header->total_price }}"></input> <p id="grand_total_show" >{{ "Rp. " . number_format($pr_header->total_price,2,',','.')  }}</p></i></b></td>
                               </tr>
            </tfoot>
            
            
        </table>
    </div>
</form>
            </div>
            {{-- end of workingschedule data  --}}
        </div>
        <!-- /.box-body -->
        <div class="box-footer"></div>
        <!-- /.box-footer-->
    </div>
    <!-- /.box -->

</section>
<!-- /.content -->
<!-- modal konfirmasi -->

<div class="modal fade" id="modal-konfirmasi" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">Konfirmasi</h4>
            </div>
            <div class="modal-body" id="konfirmasi-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" data-id="" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Deleting..." id="confirm-delete">Delete</button>
            </div>
        </div>
    </div>
</div>
<!-- end of modal konfirmasi -->
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.1/bootstrap3-typeahead.min.js"></script>
<script src="{{ asset('AdminLTE-2.3.11/plugins/input-mask/jquery.inputmask.js')}}" ></script>
<script src="{{ asset('AdminLTE-2.3.11/plugins/input-mask/jquery.inputmask.date.extensions.js')}}" ></script>
<script src="{{ asset('AdminLTE-2.3.11/plugins/input-mask/jquery.inputmask.extensions.js')}}" ></script>
<script type="text/javascript" >
    $("#formAdd :input").prop("disabled", true);
    $("#items_list :input").prop("disabled", true);
    var counter = '{{ $last_iteration }}';
    // var counter = 1;
    var id = "{{ $id }}";
    
    
    $(function() {
        $('#vehicle_id').on('select2:select', function(){
            var url = "{{ url('servicevehicle/get_veh_prop/') }}"+"/"+$('#vehicle_id').val();
            var formData = new FormData($('#formAdd')[0]);
            $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
            });
            $.ajax({
            url: url,
            type: 'GET',
            dataType: "JSON",
            success: function(data) {
                console.log(data);
                $('#beban_id').val(data.beban_id).trigger('change');
                $('#insurance_id').val(data.insurance_vendor).trigger('change');
                $('#vendor_id').val(data.default_bengkel).trigger('change');
            },
            error: function(xhr, status) {
                var a = JSON.parse(xhr.responseText);
                    // reset to remove error
                    $('.form-group').removeClass('has-error');
                    $('.help-block').hide(); // hide error span message
                    $.each(a.errors, function(key, value) {
                    $('[name="' + key + '"]').parent().addClass('has-error'); //select parent twice to select div form-group class and add has-error class
                    $('span[id^="' + key + '"]').show(); // show error message span
                    // for select2
                    if (!$('[name="' + key + '"]').is("select")) {
                        $('[name="' + key + '"]').next().text(value); //select span help-block class set text error string
                    }
                    });
                //alert('Gagal mendapatkan data Schedule Start dan Schedule End');
            }
    
            });
        });
    
    $('#items_list').on('keypress', function(e){
        var key = e.charCode || e.keyCode || 0;     
      if (key == 13) {
        e.preventDefault();
      }
    });
    
    $('#addrow').on('click',function(){
       var a = $('#data-table > tbody:last').append(`
       <tr class="tr_item">
        <td scope="col">`+counter+`</td>
            <td>
                                    <select class="item_ls select2 form-control" name="item_name_`+counter+`" id="item_name_`+counter+`" required style="width: 100%;">
                                        @foreach($master_baja as $kry)
                                        <option value="{{ $kry->id }}">{{ $kry->nama }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" class="item_ls form-control" value='new_data' name="item_id_`+counter+`" id="item_id_`+counter+`" >

            </td>
            <td>
                        <input class="item_ls form-control" id="item_desc_`+counter+`" name="item_desc_`+counter+`">
                        <span id="item_desc_`+counter+`" class="help-block" > {{ $errors->first('item_desc_`+counter+`') }} </span>
            </td>
            <td>
                        <input type="number" data-id='`+counter+`' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') )" id="qty_item_`+counter+`" name="qty_item_`+counter+`">
                        <span id="qty_item_`+counter+`" class="help-block" > {{ $errors->first('qty_item_`+counter+`') }} </span>
            </td>
            <td>
                                    <select class="item_ls select2 form-control" name="qty_satuan_item_`+counter+`" id="qty_satuan_item_`+counter+`" required style="width: 100%;">
                                        @foreach($uom as $kry)
                                        <option value="{{ $kry->nama }}">{{ $kry->nama }}</option>
                                        @endforeach
                                    </select>
            </td>
            <td>
                        <input type="number" data-id='`+counter+`' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') )" id="harga_satuan_item_`+counter+`" name="harga_satuan_item_`+counter+`">
                        <span id="harga_satuan_item_`+counter+`" class="help-block" > {{ $errors->first('harga_satuan_item_`+counter+`') }} </span>

            </td>
            <td>
                        <input type="number" data-id='`+counter+`' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') )" id="ppn_item_`+counter+`" name="ppn_item_`+counter+`">
                        <span id="ppn_item_`+counter+`" class="help-block" > {{ $errors->first('ppn_item_`+counter+`') }} </span>

            </td>
            <td>
                        <input readonly class="total form-control" type='hidden' id="harga_total_item_`+counter+`" name="harga_total_item_`+counter+`">
                        <p id="harga_total_item_show_`+counter+`"></p>
            </td>
            <td>
                        <button type='button' onclick="$(this).closest('tr').remove(); removeItem();" class="btn btn-danger btn-sm" ><span class="fa fa-trash" ></span></button> 
            </td>
       </tr>`);
       $('[name^="qty_satuan_item_"]').select2();
       $('[name^="item_name_"]').select2();
       var path = "{{ route('auto_user_define') }}";
    $('[name^="item_name_"]').typeahead({
        source:  function (query, process) {
        return $.get(path, { query: query }, function (data) {
                return process(data);
            });
        },
        displayText: function(item) {
		return item.value
	}
    });
       counter++;
      
    });
    
    
    // $('#submit').on('click',function(){
    //     var mainTable = $('#data-table').DataTable({
    //     processing : true,
    //     serverSide : true,
    //     ajax : {
    //         url : url,
    //         dataType : 'JSON',
    //         type : 'POST',
    //         data : function (data){
    //             var id =$('id').val();
    //             var transaction_id =$('transaction_id').val();
    //             var desc =$('desc').val();
    //             var qty =$('qty').val();
    //             var qty_satuan=$('qty_satuan').val();
    //             var harga_satuan=$('harga_satuan').val();
    //             var harga_total=$('harga_total').val();
    //             var url = "{{ route('servicevehicleitem') }}";
    //         }
    //     }
    //     });
    // });
    var path = "{{ route('auto_user_define') }}";

    $('[name^="item_name_"]').typeahead({
        source:  function (query, process) {
        return $.get(path, { query: query }, function (data) {
                return process(data);
            });
        },
        displayText: function(item) {
		return item.value
	}
    });
    
    $('#status').on('select2:select', function(){
            var status = $('#status').val();
            if (status === "CANCEL" || status === "REJECTED" ) {
                $('#ket_div').show();
                $('#paid').hide();
            }
            else if(status === "PAID") {
                $('#paid').show();
                $('#ket_div').hide();
            }
            else {
                $('#ket_div').hide();
                $('#paid').hide();
            }
        });

        $('#next_service').on('change', function(){
            var current = $('#service_km').val();
            var next = $('#next_service').val();
            current_vs_next(current, next)
        });

        $('#service_km').on('change', function(){
            var current = $('#service_km').val();
            var next = $('#next_service').val();
            if (next) {
            current_vs_next(current, next);
            }
        });

    }); // end function

    // fungsi rupiah convert
    function convertToRupiah(angka)
    {
        var rupiah = '';		
        var angkarev = angka.toString().split('').reverse().join('');
        for(var i = 0; i < angkarev.length; i++) if(i%3 == 0) rupiah += angkarev.substr(i,3)+'.';
        return 'Rp. '+rupiah.split('',rupiah.length-1).reverse().join('')+',00';
    }

    // fungsi validasi next service tidak boleh lebih kecil dari current km
    function current_vs_next(current, next){
        var current = parseFloat(current) || 0;
        var next = parseFloat(next) || 0;
        if (next < current) {
            alert('next service must greater than current service');
            $('#submit').attr('disabled', true);
        }
        else {
            $('#submit').attr('disabled', false);
        }
    }

          // fungsi reset all form
    function reset(){
        $('.select2').val(null).trigger('change');
    }
    function save() {
        if (save_method === "add") {
          store();
        } else {
          update();
        }
    
      }
    
      function removeItem()
      {
        $(this).closest('tr').remove();
        grand_total()
      }
      
      function deleteItem(id)
      {
        //   alert(id)
        $("#modal-konfirmasi").modal('show');
 
     $("#modal-konfirmasi").find("#confirm-delete").data("id", $(this).data('id'));
     $("#konfirmasi-body").text("Hapus Item Service?");

     $('#confirm-delete').click(function(){
        //  console.log('here we go');
         $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
            });
            $.ajax(
            {
                url: id,
                type: 'POST',
                dataType: "JSON",
                data: {
                // _method:"DELETE"
                // "id": id
                },
                success: function (response)
                {        
                $("#modal-konfirmasi").modal('hide');
                Swal.fire({
                    title: response.success,
                    // text: response.success,
                    type: 'success',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#AAA',
                    onClose: function(){
                        
                    }
                })
                },
                error: function(xhr) {
                console.log(xhr.responseText);
                }
            });
     });
          
      }
    
      function grand_total()
      {
        var sum = 0;
    
        $(".total").each(function() {
            
            var value = $(this).val();
            // add only if the value is number
            if(!isNaN(value) && value.length != 0) {
                sum += parseFloat(value);
            }
            $('#grand_total').val(sum);
            $('#grand_total_show').html(convertToRupiah(sum))
            });
      }
    
      function qty_vs_price(id)
      {
        var a =  parseInt($('#qty_item_'+id).val()) || 0; // qty qty_item_
        var b =  parseInt($('#harga_satuan_item_'+id).val()) || 0; // price harga_satuan_item_
        var total = a * b;
        $('#harga_total_item_'+id).val(total);
        if (b) {
            $('#harga_total_item_show_'+id).html( convertToRupiah(total))
        }
        grand_total()
    
      }
    
      // fungsi store with ajax
      function store() {
        var formData = new FormData($('#formAdd')[0]);
        var item = $('.item_ls').serializeArray();
        var subtotal = $('.total').serializeArray();
        var total = $('#grand_total').val();
        var index = "{{ url('purchase') }}";

        // console.log(total);
        // var item = $('.form-control > .item_ls').serializeArray();
        jQuery.each( item, function( i, field ) {
            formData.append(field.name, field.value);
        });
        jQuery.each( subtotal, function( i, field ) {
            formData.append(field.name, field.value);
        });
            formData.append('total', total);
        var url = "{{ url('purchase/update/') }}/"+id;
        $.ajaxSetup({
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
        });
        $.ajax({
          url: url,
          type: 'POST',
          dataType: "JSON",
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
            $('.form-group').removeClass('has-error');
            if (response.status) {
              Swal.fire({
                title: response.message,
                type: 'success',
                confirmButtonText: 'Close',
                confirmButtonColor: '#AAA',
                onClose: function() {
                    window.location.replace(index);

                }
              })
            }
            else {
                $('#alert').text(response.message).show();
            }
          },
          error: function(xhr, status) {
              var a = JSON.parse(xhr.responseText);
                // reset to remove error
                $('.form-group').removeClass('has-error');
                $('.help-block').hide(); // hide error span message
                $.each(a.errors, function(key, value) {
                $('[name="' + key + '"]').parent().addClass('has-error'); //select parent twice to select div form-group class and add has-error class
                $('span[id^="' + key + '"]').show(); // show error message span
                // for select2
                if (!$('[name="' + key + '"]').is("select")) {
                    $('[name="' + key + '"]').next().text(value); //select span help-block class set text error string
                }
                });
    
          }
        });
      }
    
      
        // Initialize Select2 Elements
    $('.select2').select2()
            $('.datepicker').datepicker({
                format: 'yyyy/mm/dd',
                autoclose: true
            });
    
    </script>
@endpush


