@extends('templates.header')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Details Stocks
        {{-- <small>it all starts here</small>  --}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#"><i class="fa fa-dashboard"></i> Inventory</a></li>
        <li class="active"><a href="#"> Details Stocks</a></li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
        <!-- filter field -->
        <div class="row">
            <div class="col-md-3">
              <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title">Filter</h3>
    
                  <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                    </button>
                  </div>
                  <!-- /.box-tools -->
                </div>
                <!-- /.box-header -->
                <form action="#" name="formFilter" id="formFilter">
                <div class="box-body">
                        <div class="form-group">
                                <label for="bulan" class="col-sm-4 control-label">Bulan</label>
                                <div class="col-sm-8">
                                <select class="form-control select2" name="bulan" id="bulan" style="width: 100%;">
                                    <?php
                                        for($i=0;$i<=11;$i++){
                                        $month=date('F',strtotime("first day of -$i month"));
                                        $bulan=date('m',strtotime("first day of -$i month"));
                                        echo "<option value=$bulan>$month</option> ";
                                        }
                                    ?>
                                </select>
                                <span class="help-block"></span>
                                </div>  
                                <span class="help-block"></span>
                                </div>
                                <div class="form-group">
                                    <label for="tahun" class="col-sm-4 control-label">Tahun</label>
                                    <div class="col-sm-8">
                                    <select class="form-control select2" name="tahun" id="tahun" style="width: 100%;">
                                    <?php
                                        $thn_skr = date('Y');
                                        for ($year = $thn_skr; $year <= 2025; $year++) {
                                        ?>
                                            <option value="<?php echo $year ?>"><?php echo $year ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                    <span class="help-block"></span>
                                    </div>  
                                    <span class="help-block"></span>
                                    </div>
                            
                    </div>
                  </form>
                <div class="box-footer">
                        <button type="button" class="btn btn-default " id="resetFilter" name="resetFilter" ><span class="fa fa-refresh"></span> Reset</button>
                        <button type="button" class="btn btn-primary pull-right" id="btnFilter" name="btnFilter" ><span class="fa fa-filter"></span> Filter</button>
                </div>
                <!-- /.box-body -->
              </div>
              <!-- /.box -->
            </div>
        </div>
        <!-- end of filter field -->

    <!-- Default box -->
    <div class="box">
        <div class="box-body">

            {{-- sub menu  --}}
            <div style="margin-bottom: 20px">
               
            </div>

            @if(session('status'))
            <div class="alert alert-success alert-dismissible fade in"> {{ session('status') }}
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            </div>
            @endif

            {{-- end of sub menu  --}}

            {{-- table data of workingschedule  --}}
            <div class="table-responsive">
                <table id="data-tables" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Barang</th>
                            <th>Kode Barang</th>
                            <th>PR Number</th>
                            <th>ID Pembelian</th>
                            {{-- <th>Status</th> --}}
                            {{-- <th>Bukti Bayar</th> --}}
                            {{-- <th>Remarks</th> --}}
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->master_baja->nama }}</td>
                            <td>{{ $row->master_baja->kode_barang }}</td>
                            <td>{{ $row->header->pr_number }}</td>
                            <td>{{ $row->id_pembelian }}</td>
                            <td><a href='{{ route('edit_pr', $row->pr_header) }}' class='btn btn-success btn-xs'><span class='fa fa-eye'></span></a></td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
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

<script>
$(function() {
    var url = "{{ route('inventory_list') }}";
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
    var mainTable = $('#data-table').DataTable({
    processing : true,
    serverSide : true,
    ajax : {
        url : url,
        dataType : 'JSON',
        type : 'POST',
        data : function (data){
        data.bulan = $('#bulan').val();
        data.tahun = $('#tahun').val();
        data.filter_beban = $('#filter_beban').val();
       }
   },
   columns : [
        {data : 'no'},
        {data : 'nama_barang'},
        {data : 'kode_barang'},
        {data : 'pr_number'},
        {data : 'current_stock'},
        {data : 'status'},
        {data : 'paid_date_file'},
        {data : 'remarks'},
        {data : 'action'}
     ],
    //Set column definition initialisation properties.
     columnDefs: [
            { 
                targets: [0, -1], //last column
                orderable: false, //set not orderable
            }
        ],

});
   
var selectedRow;
   

   $('#data-table').on('click', '.delete', function (e) {
     e.preventDefault();
     selectedRow = mainTable.row( $(this).parents('tr') );
 
     $("#modal-konfirmasi").modal('show');
 
     $("#modal-konfirmasi").find("#confirm-delete").data("id", $(this).data('id'));
     $("#konfirmasi-body").text("Hapus Transaksi Pembelian?");
   });
   $('#btnFilter').click(function() {
       //alert('dumbass');
       $('#data-table').DataTable().ajax.reload();
       var bulan = $('#bulan').val();
        var tahun = $('#tahun').val();
        var filter_beban = $('#filter_beban').val();
     });
 
     $('#resetFilter').click(function() {
       $('#formFilter')[0].reset();
       $('#data-table').DataTable().ajax.reload();
     });

    $('#resetFilter').click(function() {
    $('#bulan').val(null).trigger('change'); // reset dropdownbulan
    $('#tahun').val(null).trigger('change'); // reset dropdowntahun
    $('#filter_beban').val(null).trigger('change'); // reset dropdowntahun
    $('#formFilter')[0].reset(); // reset input text
    $('#data-table').DataTable().ajax.reload();})
 
   $('#confirm-delete').click(function(){
       var deleteButton = $(this);
       var id           = deleteButton.data("id");
 
       deleteButton.button('loading');
 
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
           deleteButton.button('reset');
 
           selectedRow.remove().draw();
 
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
 });
 </script>
 @endpush
 
 
 