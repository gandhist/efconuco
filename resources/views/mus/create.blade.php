@extends('templates.header')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Add Data Material Usage
        {{-- <small>it all starts here</small>  --}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#"><i class="fa fa-dashboard"></i> Penjualan</a></li>
        <li class="active"><a href="#">Transaksi Penjualan</a></li>
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
                    <div class="form-group">
                        <label for="trans_no">No Transaksi</label>
                        <input readonly name="trans_no" type="text" class="form-control" id="trans_no" value="{{ $trans_no }}" style="width: 100%;">
                        {{-- <span class="help-block" >{{ $errors->first('trans_no') }} </span> --}}
                    </div>
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="text" class="form-control" autocomplete="off" data-provide="datepicker" data-date-format="yyyy/mm/dd" name="tanggal" id="tanggal" placeholder="yyyy/mm/dd" required>
                        <span id="tanggal" class="help-block" >{{ $errors->first('tanggal') }} </span>
                    </div>
                    <div class="form-group">
                        <label for="nama_pembeli">Request By</label>
                        <input type="text" class="form-control" id="nama_pembeli" name="nama_pembeli" value="{{ old("nama_pembeli")}}" >
                        <span id="nama_pembeli" class="help-block" > {{ $errors->first('nama_pembeli') }} </span>
                    </div>
                    <div class="form-group" id="ket_div">
                        <label for="desc">Remarks</label>
                        <textarea name="desc" text="text" class="form-control" id="desc" value="{{old("desc")}}" placeholder="Keterangan"></textarea>
                        <span id="desc" class="help-block" > {{ $errors->first('desc') }} </span>
                    </div>
                    
                </div>
                    <!-- end off bilah tengah -->
                    <div class="col-xs-4">
                    </div>
                    <!-- end off bilah kanan -->
                    <div class="col-xs-4">
                    </div>
                </div>
                    <!-- /.box-body -->
                
                <div class="input_fields_wrap">
</div>

                    <div class="box-footer">
                            <a href="{{url("sales")}}" class="btn btn-default">Cancel</a>
                            <button type="button" id="submit" name="submit" onclick="store()" class="btn btn-primary">Create Data</button>
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
                    <!-- <th scope="col" >Harga Satuan</th>
                    <th scope="col" >PPN (10%)</th>
                    <th scope="col" >PPH 23</th>
                    <th scope="col" >PPH P4</th>
                    <th scope="col" >Diskon</th>
                    <th scope="col" >Harga Total</th> -->
                    <th scope="col" >Action</th>
                    <th scope="col" ><button id='addrow' type='button' class="btn btn-primary"> <span class="fa fa-plus" ></span></button></th>
                </tr>
            </thead>
            <tbody>
                
            </tbody>
            <tfoot>
                <!-- <tr>
                                <td colspan='9' align='right' >Total</td>
                             <td ><b><i><input readonly class="form-control" type="hidden" id='grand_total'></input> <p id="grand_total_show" ></p> </i></b></td>
                               </tr> -->
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
    var counter = 1;
    
    
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
                                    <select data-id='`+counter+`' class="item_ls select2 form-control" name="item_name_`+counter+`" id="item_name_`+counter+`" required style="width: 100%;" onchange="get_prop( $(this).data('id') )" ">
                                        @foreach($master_baja as $kry)
                                        <option value="{{ $kry->id }}">{{ $kry->nama }}</option>
                                        @endforeach
                                    </select>
            </td>
            <td>
                        <input class="item_ls form-control"  id="item_desc_`+counter+`" name="item_desc_`+counter+`">
                        <span id="item_desc_`+counter+`" class="help-block" > {{ $errors->first('item_desc_`+counter+`') }} </span>
            </td>
            <td>
                        <input type="number" data-id='`+counter+`' class="item_ls form-control" onchange="qty_vs_price( $(this).data('id') ); curstock( $(this).data('id') )" id="qty_item_`+counter+`" name="qty_item_`+counter+`">
                        <input type="hidden" data-id='`+counter+`' id="current_stock_`+counter+`" name ="current_stock_`+counter+`" >
                        <span id="qty_item_`+counter+`" class="help-block" > {{ $errors->first('qty_item_`+counter+`') }} </span>
            </td>
            <td>
                        <button type='button' onclick="$(this).closest('tr').remove(); removeItem();" class="btn btn-danger btn-sm" ><span class="fa fa-trash" ></span></button> 
            </td>
       </tr>`);
       $('[name^="qty_satuan_item_"]').select2();
       $('[name^="item_name_"]').select2();
       counter++;
      
    });

    var path = "{{ route('auto_user_define') }}";
    $('input.typeahead').typeahead({
        source:  function (query, process) {
        return $.get(path, { query: query }, function (data) {
                return process(data);
            });
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



    });  // end function

    // fungsi rupiah convert
    function convertToRupiah(angka)
    {
        var rupiah = '';		
        var angkarev = angka.toString().split('').reverse().join('');
        for(var i = 0; i < angkarev.length; i++) if(i%3 == 0) rupiah += angkarev.substr(i,3)+'.';
        return 'Rp. '+rupiah.split('',rupiah.length-1).reverse().join('')+',00';
    }

    // validasi current stock
    function curstock(id) {
        var user_stock = parseInt($('#qty_item_'+id).val());
        var current_stock = parseInt($('#current_stock_'+id).val());
        if (user_stock > current_stock) {
            $('#qty_item_'+id).val(current_stock);
            alert('stok tidak tersedia');
        }

    }

    // get prop
    function get_prop(id) {
        let value = $('#item_name_'+id).val();
        var url = "{{ url('sales/get_prop/') }}/"+value;
       $.ajaxSetup({
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
       });
       $.ajax(
       {
         url: url,
         type: 'GET',
         dataType: "JSON",
         data: {
           // _method:"DELETE"
           // "id": id
         },
         success: function (response)
         {
             $('#current_stock_'+id).val(response[0].current_stock);
         },
         error: function(xhr) {
           console.log(xhr.responseText);
         }
       });
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
        var ppn = parseInt($('#ppn_item_'+id).val()) || 0;
        var pph23 = parseFloat($('#pph23_item_'+id).val()) || 0;
        var diskon = parseFloat($('#diskon_item_'+id).val()) || 0;
        var disc_value = (a * b) * (diskon / 100);
        var total_before_ppn = (a * b) - disc_value ;
        var ppn_value = total_before_ppn * (ppn / 100);
        var pph23_value = total_before_ppn * (pph23 / 100);
        var total = total_before_ppn + ppn_value + pph23_value;
        // alert(ppn);
       
        if (b) {
            $('#harga_total_item_'+id).val(total);
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
        var index = "{{ url('mus') }}";

        // console.log(total);
        // var item = $('.form-control > .item_ls').serializeArray();
        jQuery.each( item, function( i, field ) {
            formData.append(field.name, field.value);
        });
        jQuery.each( subtotal, function( i, field ) {
            formData.append(field.name, field.value);
        });
            formData.append('total', total);
        var url = "{{ url('mus/store') }}";
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


