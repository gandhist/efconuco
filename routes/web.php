<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::auth();

Route::group(['middleware' => 'auth'], function () {

	Route::group(['middleware' => 'auth.input'], function () {
		Route::get('', 'HomeController@index');
	});

	Route::group(['middleware' => 'auth.admin'], function () {
		Route::resources([
			'users' => 'UserController',
		]);
		Route::resources([
			'user_role' => 'UserRoleController',
		]);
	});
});

route::get('autocomplete_user_define','PurchaseRequestController@autocomplete_item_name')->name('auto_user_define');

// route master stock
Route::group(['middleware' => 'authorization:master_baja'], function () {
	route::resource('master_stock','MasterStockController');
});


// route Pembelian
Route::group(['middleware' => 'authorization:pembelian'], function () {
	Route::group(['prefix'=>'purchase'], function (){
		route::get('/','PurchaseRequestController@index');
		route::get('create','PurchaseRequestController@create');
		route::post('store','PurchaseRequestController@store');
		route::get('edit/{id}','PurchaseRequestController@edit')->name('edit_pr');
		route::get('show/{id}','PurchaseRequestController@show')->name('show_pr');
		route::patch('update/{id}','PurchaseRequestController@update');
		route::post('destroy/{id}','PurchaseRequestController@destroy')->name('delete_pr');
		route::post('remove_item','PurchaseRequestController@removeItem');
		route::post('list','PurchaseRequestController@pr_list')->name('pr_list');
	});
});
// end of route pembelian

// route Inventory
Route::group(['middleware' => 'authorization:inventory'], function () {
	Route::group(['prefix'=>'inventory'], function (){
		route::get('/','InventoryController@index');
		route::get('create','InventoryController@create');
		route::post('store','InventoryController@store');
		route::patch('update/{id}','InventoryController@update');
		route::get('show/{id}','InventoryController@show');
		route::post('destroy/{id}','InventoryController@destroy')->name('delete_pr');
		route::post('list','InventoryController@inventory_list')->name('inventory_list');
	});
});
// end of route Inventory

// route sales
Route::group(['middleware' => 'authorization:sales'], function () {
	Route::group(['prefix'=>'sales'], function (){
		route::get('/','SalesController@index');
		route::get('create','SalesController@create');
		route::post('store','SalesController@store');
		route::get('edit/{id}','SalesController@edit')->name('edit_so');
		route::get('show/{id}','SalesController@show')->name('show_so');
		route::patch('update/{id}','SalesController@update');
		route::post('destroy/{id}','SalesController@destroy')->name('delete_so');
		route::post('remove_item','SalesController@removeItem');
		route::post('list','SalesController@so_list')->name('so_list');
		route::get('get_prop/{id}','SalesController@barang_prop')->name('get_barang_prop');
	});
});
// end of route sales

// route sales
Route::group(['middleware' => 'authorization:mus'], function () {
	Route::group(['prefix'=>'mus'], function (){
		route::get('/','MusController@index');
		route::get('create','MusController@create');
		route::post('store','MusController@store');
		route::get('edit/{id}','MusController@edit')->name('edit_mus');
		route::get('show/{id}','MusController@show')->name('show_mus');
		route::patch('update/{id}','MusController@update');
		route::post('destroy/{id}','MusController@destroy')->name('delete_mus');
		route::post('remove_item','MusController@removeItem');
		route::post('list','MusController@mus_list')->name('mus_list');
		route::get('get_prop/{id}','MusController@barang_prop')->name('get_barang_prop');
	});
});
// end of route sales

// Report

Route::group(['prefix' => 'report'], function () {
	Route::get('lembur', 'ReportOvertimeController@overtime');
	Route::get('lembur/details/{id}', 'ReportOvertimeController@overtime_details');
	// rekap beban
	Route::any('rekap/beban', 'ReportRekapBebanController@rekap_by_beban');
	Route::any('rekap/beban/{tahun}/{id}', 'ReportRekapBebanController@rekap_beban_by_beban_id');
	Route::post('overtimeList', 'ReportOvertimeController@overtimeList');
	Route::post('rptOvertimeList/{id}', 'ReportOvertimeController@rptOvertimeList');
	Route::group(['prefix' => 'excel'], function () {
		Route::get('export_overtime/{period}/{tahun}/{nik}', 'ReportOvertimeController@export_overtime_xls');
	});
	Route::group(['prefix' => 'pdf'], function () {
		Route::get('export_overtime/{period}/{tahun}/{nik}', 'ReportOvertimeController@export_overtime_pdf');
	});
});


// end of Report

// Route Kas Kecil
Route::group(['prefix' => 'stock'], function () {
	// transaksi Stock Pantry
	// Route::group(['middleware' => 'authorization:stock'], function ()
	// {
		Route::get('info', 'StockController@stock_info');
		Route::any('restock', 'StockController@restock');
		Route::get('restock/{id}', 'StockController@edit_restock');
		Route::get('restock_after_paid/{id}', 'StockController@edit_restock_after_paid');
		Route::post('bulk_add','StockController@bulk_add');
		Route::patch('bulk_update/{id}','StockController@bulk_update');
		Route::post('prepare_restock','StockController@prepare_restock');
		Route::get('purchase','StockController@purchase');
		Route::post('purchase_list','StockController@pr_list')->name('purchaseList');
		Route::post('tro_list','StockController@TroHeaderList')->name('TroHeaderList');
		Route::post('out_list','StockController@OutStockList')->name('OutStockList');
		Route::get('tro_stock','StockController@tr_stock');
		Route::post('tro_add','StockController@tro_add');
		Route::post('chained_gudang','StockController@chained_gudang');
		Route::get('get_balance/{id_barang}/{id_gudang}','StockController@get_balance');
		Route::get('io_stock','StockController@io_stock');
		Route::post('out_add','StockController@out_add');
		Route::get('out_edit/{id}','StockController@out_edit');

	// });
});
// end of kas kecil

// Route atk
Route::group(['prefix' => 'atk'], function () {
	// transaksi Stock Pantry
	// Route::group(['middleware' => 'authorization:stock'], function ()
	// {
		Route::get('info', 'AtkController@stock_info');
		Route::any('restock', 'AtkController@restock');
		Route::get('restock/{id}', 'AtkController@edit_restock');
		Route::get('restock_after_paid/{id}', 'AtkController@edit_restock_after_paid');
		Route::post('bulk_add','AtkController@bulk_add');
		Route::patch('bulk_update/{id}','AtkController@bulk_update');
		Route::post('prepare_restock','AtkController@prepare_restock');
		Route::get('purchase','AtkController@purchase');
		Route::post('purchase_list','AtkController@pr_list')->name('AtkPurchaseList');
		Route::post('tro_list','AtkController@TroHeaderList')->name('AtkTroHeaderList');
		Route::post('out_list','AtkController@OutStockList')->name('AtkOutStockList');
		Route::get('tro_stock','AtkController@tr_stock');
		Route::post('tro_add','AtkController@tro_add');
		Route::post('chained_gudang','AtkController@chained_gudang');
		Route::get('get_balance/{id_barang}/{id_gudang}','AtkController@get_balance');
		Route::get('io_stock','AtkController@io_stock');
		Route::post('out_add','AtkController@out_add');
		Route::get('out_edit/{id}','AtkController@out_edit');

	// });
});
// end of atk

//provinsi
Route::group(['middleware' => 'authorization:master_provinsi'], function () {
	Route::get('/provinsi', 'ProvinsiController@index');
	Route::delete('/provinsi/{id}', 'ProvinsiController@destroy');
	Route::get('/provinsi/create', 'ProvinsiController@create');
	Route::post('/provinsi/store', 'ProvinsiController@store')->name('provinsistore');
	Route::get('/provinsi/{id}/edit', 'ProvinsiController@edit')->name('proedit');
	Route::patch('/provinsi/{id}/update', 'ProvinsiController@update')->name('proupdate');
});

route::resource('gudang','GudangController');

//level
Route::group(['middleware' => 'authorization:master_level'], function () {
	Route::get('/level', 'LevelController@index');
	Route::delete('/level/{id}', 'LevelController@destroy');
	Route::get('/level/create', 'LevelController@create');
	Route::post('/level/store', 'LevelController@store')->name('levelstore');
	Route::get('/level/{id}/edit', 'LevelController@edit')->name('leveledit');
	Route::patch('/level/{id}/update', 'LevelController@update')->name('levelupdate');
});
//kota
Route::group(['middleware' => 'authorization:master_kota'], function () {
	Route::get('/kota', 'KotaController@index');
	Route::delete('/kota/{id}', "KotaController@destroy");
	Route::get('/kota/create', 'KotaController@create');
	Route::post('/kota/store', 'KotaController@store')->name('kotastore');
	Route::get('/kota/{id}/edit', 'KotaController@edit')->name('kotaedit');
	Route::patch('/kota/{id}/update', 'KotaController@update')->name('kotaupdate');
});
//kantor
Route::group(['middleware' => 'authorization:master_kantor'], function () {
	Route::get('/kantor', 'KantorController@index');
	Route::delete('/kantor/{id}', 'KantorController@destroy');
	Route::get('/kantor/create', 'KantorController@create');
	Route::post('/kantor/store', 'KantorController@store')->name('kantorstore');
	Route::get('/kantor/{id}/edit', 'KantorController@edit')->name('kantoredit');
	Route::patch('/kantor/{id}/update', 'KantorController@update')->name('kantorupdate');
});

//jabatan
Route::group(['middleware' => 'authorization:master_jabatan'], function () {
	Route::get('/jabatan', 'JabatanController@index');
	Route::get('/jabatan/create', 'JabatanController@create');
	Route::post('/jabatan/store', 'JabatanController@store');
	Route::post('/jabatan/{id}', 'JabatanController@destroy');
	Route::get('/jabatan/{id}/edit', 'JabatanController@edit')->name('jabatanedit');
	Route::patch('/jabatan/{id}/update', 'JabatanController@update')->name('jabatanupdate');
});

//workinghour
Route::get('workinghour', 'WorkingHourController@index');
Route::get('workinghour/create', 'WorkingHourController@create');
Route::post('workinghour', 'WorkingHourController@store');
Route::delete('workinghour/{workingHour}', 'WorkingHourController@destroy');
Route::get('workinghour/{workingHour}/edit', 'WorkingHourController@edit');
Route::patch('workinghour/{workingHour}', 'WorkingHourController@update');

//workingschedule
Route::get('workingschedule', 'WorkingScheduleController@index');
Route::get('workingschedule/create', 'WorkingScheduleController@create');
Route::post('workingschedule', 'WorkingScheduleController@store');
Route::delete('workingschedule/{workingSchedule}', 'WorkingScheduleController@destroy');
Route::get('workingschedule/{workingSchedule}/edit', 'WorkingScheduleController@edit');
Route::patch('workingschedule/{workingSchedule}', 'WorkingScheduleController@update');

//workingtype
Route::get('workingtype', 'WorkingTypeController@index');
Route::get('workingtype/create', 'WorkingTypeController@create');
Route::post('workingtype', 'WorkingTypeController@store');
Route::delete('workingtype/{workingType}', 'WorkingTypeController@destroy');
Route::get('workingtype/{workingType}/edit', 'WorkingTypeController@edit');
Route::patch('workingtype/{workingType}', 'WorkingTypeController@update');


//libur
Route::group(['middleware' => 'authorization:master_libur'], function () {
	Route::get('/libur', 'LiburController@index');
	Route::delete('/libur/{id}', 'LiburController@destroy');
	Route::get('/libur/create', 'LiburController@create');
	Route::post('/libur/store', 'LiburController@store')->name('liburstore');
	Route::get('/libur/{id}/edit', 'LiburController@edit')->name('liburedit');
	Route::patch('/libur/{id}/update', 'LiburController@update')->name('liburupdate');
});
//Lembur
Route::group(['middleware' => 'authorization:master_lembur'], function () {
	Route::get('/lembur', 'LemburController@index');
	Route::delete('/lembur/{id}', 'LemburController@destroy');
	Route::get('/lembur/create', 'LemburController@create');
	Route::post('/lembur/store', 'LemburController@store')->name('lemburstore');
	Route::get('/lembur/{id}/edit', 'LemburController@edit')->name('lemburedit');
	Route::patch('/lembur/{id}/update', 'LemburController@update')->name('lemburupdate');
});
//Lemburrest
Route::group(['middleware' => 'authorization:master_lembur_rest'], function () {
	Route::get('/lemburrest', 'LemburRestController@index');
	Route::delete('/lemburrest/{id}', 'LemburRestController@destroy');
	Route::get('/lemburrest/create', 'LemburRestController@create');
	Route::post('/lemburrest/store', 'LemburRestController@store')->name('lemburreststore');
	Route::get('/lemburrest/{id}/edit', 'LemburRestController@edit')->name('lemburrestedit');
	Route::patch('/lemburrest/{id}/update', 'LemburRestController@update')->name('lemburrestupdate');
});

//leavetype
Route::group(['middleware' => 'authorization:master_leave_type'], function () {
	Route::get('/leavetype', 'LeaveTypeController@index');
	Route::delete('/leavetype/{id}', 'LeaveTypeController@destroy');
	Route::get('/leavetype/create', 'LeaveTypeController@create');
	Route::post('/leavetype/store', 'LeaveTypeController@store')->name('leavetypestore');
	Route::get('/leavetype/{id}/edit', 'LeaveTypeController@edit')->name('leavetypeedit');
	Route::patch('/leavetype/{id}/update', 'LeaveTypeController@update')->name('leavetypeupdate');
});

//KaryawanLeave

Route::group(['middleware' => 'authorization:karyawan_leave'], function () {
	Route::get('/karyawanleave', 'KaryawanLeaveController@index');
	Route::get('/karyawanleave/create', 'KaryawanLeaveController@create');
	Route::post('/karyawanleave', 'KaryawanLeaveController@store');
	Route::delete('/karyawanleave/{id}', 'KaryawanLeaveController@destroy');
	Route::get('/karyawanleave/{id}/show', 'KaryawanLeaveController@show');
	Route::get('/karyawanleave/{id}/{trailId}/edit', 'KaryawanLeaveController@edit');
	Route::patch('/karyawanleave/{id}/update', 'KaryawanLeaveController@update');
});

//KaryawanLeaveTrail
Route::group(['middleware' => 'authorization:karyawan_leave_trail'], function () {
	Route::get('/karyawanleavetrail', 'KarLevTrailController@index');
	Route::delete('/karyawanleavetrail/{id}', 'KarLevTrailController@destroy');
	Route::get('/karyawanleavetrail/create', 'KarLevTrailController@create');
	Route::post('/karyawanleavetrail/store', 'KarLevTrailController@store')->name('karyawanleavetrailstore');
	Route::get('/karyawanleavetrail/{id}/edit', 'KarLevTrailController@edit')->name('karyawanleavetrailedit');
	Route::patch('/karyawanleavetrail/{id}/update', 'KarLevTrailController@update')->name('karyawanleavetrailupdate');
});
//KaryawanLeaveQuota
Route::group(['middleware' => 'authorization:karyawan_leave_quota'], function () {
	Route::get('/karyawanleavequota', 'KarLevQuoController@index');
	Route::delete('/karyawanleavequota/{id}', 'KarLevQuoController@destroy');
	Route::get('/karyawanleavequota/create', 'KarLevQuoController@create');
	Route::post('/karyawanleavequota/store', 'KarLevQuoController@store')->name('karyawanleavequotastore');
	Route::get('/karyawanleavequota/{id}/edit', 'KarLevQuoController@edit')->name('karyawanleavequotaedit');
	Route::get('/karyawanleavequota/{id}/show', 'KarLevQuoController@show')->name('karyawanleavequotashow');
	Route::patch('/karyawanleavequota/{id}/update', 'KarLevQuoController@update')->name('karyawanleavequotaupdate');

	//KaryawanLeaveLog
	// Route::group(['middleware' => 'authorization:karyawan_leave_log'],function () {
	// Route::get('/karyawanleavelog','KarLevLogController@index');
	// Route::delete('/karyawanleavelog/{id}', 'KarLevLogController@destroy');
	// Route::get('/karyawanleavelog/create', 'KarLevLogController@create');
	// Route::post('/karyawanleavelog/store', 'KarLevLogController@store')->name('karyawanleavelogstore');
	// Route::get('/karyawanleavelog/{karyawanleavelog}/edit', 'KarLevLogController@edit')->name('karyawanleavelogedit');
	// Route::patch('/karyawanleavelog/{karyawanleavelog}/update', 'KarLevLogController@update')->name('karyawanleavelogupdate');
	// });

	//Kuota Cuti
	Route::get('kuotacuti', 'KuotaCutiController@index');
	Route::get('kuotacuti/create', 'KuotaCutiController@create');
	Route::get('kuotacuti/{kuotaCuti}', 'KuotaCutiController@show');
	Route::post('kuotacuti', 'KuotaCutiController@store');
	Route::get('delete/{kuotaCuti}', 'KuotaCutiController@destroy');
	Route::get('kuotacuti/{kuotaCuti}/edit', 'KuotaCutiController@edit')->name('kuotaCutiEdit');
	Route::patch('kuotacuti/{kuotaCuti}', 'KuotaCutiController@update')->name('kuotaCutiPatch');
	// route for deleting multiple rows in the same time
	Route::post('del/{kuotaCuti}', 'KuotaCutiController@del');
});

//Karyawan Sakit
Route::resource('sakit', 'SakitController');

//Karyawan Sakit Trail
Route::resource('sakittrail', 'SakitTrailController');

//Karyawan Permission
Route::resource('karyawanpermission', 'KaryawanPermissionController');

//Karyawan Permission Trail
Route::resource('karyawanpermissiontrail', 'KaryawanPermissionTrailController');


//Bebankerja
Route::get('beban', 'BebanController@index');
Route::get('beban/create', 'BebanController@create');
Route::post('beban/store', 'BebanController@store');
Route::post('beban/{id}', 'BebanController@destroy')->name('deletedbeban');
Route::get('beban/{id}', 'BebanController@edit')->name('editbeban');
Route::patch('beban/{id}', 'BebanController@update')->name('updatebeban');

//rekapkehadiran
Route::get('/rekapkehadiran', 'RekapKehadiranController@index');
Route::post('/rekapkehadiran', 'RekapKehadiranController@rekapkehadiranlist')->name('rekapkehadiranlist');
//report excel rekap kehadiran
Route::post('exportkehadiran', 'RptKehadiranController@exportRptKehadiran')->name('export_rptkehadiran');
Route::post('exportkehadiran_PDF', 'RptKehadiranController@exportRptKehadiranPDF')->name('exportRptKehadiran_PDF');

//Laporan Security
Route::get('security', 'SecurityController@index');
Route::post('security', 'SecurityController@securitylist')->name('securitylist');

//report Excel security
// Route::post('rptSecurity','RptSecurityController@rptSecurity')->name('rptsecurity');
Route::post('export', 'RptSecurityController@exportSecurity')->name('export_security');
Route::post('export_PDF', 'RptSecurityController@exportPDF')->name('export_PDF');

//vehicle
Route::get('vehicle','VehicleController@index');
Route::get('vehicle/create','VehicleController@create');
Route::post('vehicle/vehiclelist','VehicleController@vehiclelist')->name('vehiclelist');
Route::post('vehicle/store','VehicleController@store');
Route::get('vehicle/show/{id}','VehicleController@show')->name('showvehicle');
Route::DELETE('vehicle/{id}','VehicleController@destroy')->name('deletedvehicle');
Route::get('vehicle/edit{id}','VehicleController@edit')->name('editvehicle');
Route::patch('vehicle/update{id}','VehicleController@update')->name('updatevehicle');

//Vendor
Route::get('listVendor', 'VendorController@index');
Route::get('vendor/create', 'VendorController@create');
Route::post('vendor/store', 'VendorController@store');
Route::post('vendor/{id}', 'VendorController@destroy')->name('deletedvendor');
Route::get('vendor/edit/{id}', 'VendorController@edit')->name('editvendor');
Route::patch('vendor/update/{id}', 'VendorController@update')->name('updatevendor');

//Category Vendor
Route::get('category', 'CategoryController@index');
Route::get('category/create', 'CategoryController@create');
Route::post('category/store', 'CategoryController@store');
Route::post('category/{id}', 'CategoryController@destroy');
Route::get('category/{id}', 'CategoryController@edit')->name('editcategory');
Route::patch('category/{id}', 'CategoryController@update')->name('updatecategory');

//Insurance
Route::get('insurance', 'InsuranceController@index');
Route::get('insurance/create', 'InsuranceController@create');
Route::post('insurance/store', 'InsuranceController@store');
Route::post('insurance/{id}', 'InsuranceController@destroy')->name('deletedinsurance');
Route::get('insurance/{id}', 'InsuranceController@edit')->name('editinsurance');
Route::patch('insurance/{id}', 'InsuranceController@update')->name('updateinsurance');

//Tipe Transaksi
Route::resource('tipetransaksi', 'TipeTransaksiController');

//Metode Pembayaran
Route::resource('metodepembayaran', 'MetodePembayaranController');

//servicevehicle
Route::get('servicevehicle', 'ServiceVehicleController@index');
Route::get('servicevehicle/create', 'ServiceVehicleController@create');
Route::post('servicevehicle/store', 'ServiceVehicleController@store');
Route::post('servicevehicleitem/storeitem', 'ServiceVehicleController@storeitem')->name('servicevehicleitem');
Route::post('servicevehicle/servicevehiclelist', 'ServiceVehicleController@servicevehiclelist')->name('servicevehiclelist');
Route::post('servicevehicle/servicevehiclelistitem', 'ServiceVehicleController@servicevehiclelistitem')->name('servicevehiclelistitem');
Route::get('servicevehicle/{id}', 'ServiceVehicleController@edit')->name('editservicevehicle');
Route::get('servicevehicle/get_veh_prop/{id}', 'ServiceVehicleController@get_prop_vehicle')->name('vehProp');
Route::patch('servicevehicle/{id}', 'ServiceVehicleController@update')->name('updateservicevehicle');
Route::post('servicevehicle/{id}', 'ServiceVehicleController@destroy')->name('deletedservicevehicle');

