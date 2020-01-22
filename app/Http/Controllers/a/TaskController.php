<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Tasks;
use App\Drivers;
use App\Cars;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index(){
        $data['cars'] = Cars::all();
        $data['drivers'] = Drivers::all();
        $data['tasks'] = Tasks::where("is_started","=",1)
            ->where("is_finished","=",0)
            ->where("is_canceled","=", null)
            ->where("is_draft","=",0)
            ->orderBy("task_date_start", "desc")
            ->get();
        return view('task/index')->with($data);
    }

    public function approved(){
        $data['cars'] = Cars::all();
        $data['drivers'] = Drivers::all();
        $data['tasks'] = Tasks::where("is_started","=",0)
            ->where("is_finished","=",0)
            ->where("is_canceled","=", null)
            ->where("is_draft","=",0)
            ->orderBy("task_date_start", "desc")
            ->get();
        return view('task/approved')->with($data);
    }

    public function pending(){
        $data['cars'] = Cars::all();
        $data['drivers'] = Drivers::all();
        $data['tasks'] = Tasks::where("is_started","=",0)
            ->where("is_finished","=",0)
            ->where("is_canceled","=", null)
            ->where("is_draft","=",1)
            ->orderBy("task_date_start", "desc")
            ->get();
        return view('task/pending')->with($data);
    }

    public function history(Request $request){
        $data['cars'] = Cars::all();
        $data['drivers'] = Drivers::all();
        
        $from = $request->from ? \Carbon\Carbon::createFromFormat("d/m/Y", $request->from) : \Carbon\Carbon::now();
        $to = $request->to ? \Carbon\Carbon::createFromFormat("d/m/Y", $request->to) : \Carbon\Carbon::now();

        $data['tasks'] = Tasks::where(function($q) {
                  $q->where("is_finished","=",1)
                    ->orWhere("is_canceled","=",1);
              })
            ->isDriver($request->dr)  
            ->isCar($request->cr)  
            ->whereDate("finished_date", ">=", $from->format('Y-m-d'))
            ->whereDate("finished_date", "<=", $to->format('Y-m-d'))
            ->orderBy("finished_date", "desc")
            ->get();
            
        return view('task/history')->with($data);
    }

    public function getAll(){
        $data = 
        Tasks::leftJoin("cars","tasks.car_id", "=","cars.id")
                ->leftJoin("drivers", "tasks.driver_id", "=", "drivers.id")
                ->selectRaw("
                    tasks.*, 
                    concat(cars.name, ' - ', cars.license) as car_name, 
                    cars.license as car_license, 
                    drivers.name as driver_name,
                    IF(tasks.is_canceled = 1, 'CANCELED', 
                    IF(tasks.is_finished = 1, 'FINISHED',
                    IF(tasks.is_started = 1, 'STARTED',
                    IF(tasks.is_draft = 1, 'PENDING','OPEN')))) as status
                    ")
                ->get();
        return response()->json(['data'=>$data]);
    }

    public function getApproved(){
        $data = 
        Tasks::leftJoin("cars","tasks.car_id", "=","cars.id")
                ->leftJoin("drivers", "tasks.driver_id", "=", "drivers.id")
                ->leftJoin("users as created_user", "tasks.created_by", "=", "created_user.id")
                ->leftJoin("users as approved_user", "tasks.approved_by", "=", "approved_user.id")
                ->selectRaw("
                    tasks.*, 
                    concat(cars.name, ' - ', cars.license) as car_name, 
                    cars.license as car_license, 
                    drivers.name as driver_name,
                    created_user.name as created_name,
                    approved_user.name as approved_name,
                    IF(tasks.is_canceled = 1, 'CANCELED', 
                    IF(tasks.is_finished = 1, 'FINISHED',
                    IF(tasks.is_started = 1, 'STARTED',
                    IF(tasks.is_draft = 1, 'PENDING','OPEN')))) as status
                    ")
                ->where("tasks.is_draft","=",0)
                ->where("tasks.is_started","=",0)
                ->whereDate('task_date_start', '>=', date('Y-m-d'))
                ->orderBy("tasks.task_date_start", "desc")
                ->get();
        return response()->json(['data'=>$data]);
    }

    public function getOngoing(){
        $data = 
        Tasks::leftJoin("cars","tasks.car_id", "=","cars.id")
                ->leftJoin("drivers", "tasks.driver_id", "=", "drivers.id")
                ->leftJoin("users", "tasks.approved_by", "=", "users.id")
                ->selectRaw("
                    tasks.*, 
                    concat(cars.name, ' - ', cars.license) as car_name, 
                    cars.license as car_license, 
                    drivers.name as driver_name,
                    users.name as approved_name,
                    IF(tasks.is_canceled = 1, 'CANCELED', 
                    IF(tasks.is_finished = 1, 'FINISHED',
                    IF(tasks.is_started = 1, 'STARTED',
                    IF(tasks.is_draft = 1, 'PENDING','OPEN')))) as status
                    ")
                ->where("tasks.is_draft","=",0)
                ->where("tasks.is_started","=",1)
                ->where("tasks.is_finished","=",0)
                ->whereDate('task_date_start', '>=', date('Y-m-d'))
                ->orderBy("tasks.task_date_start", "desc")
                ->get();
        return response()->json(['data'=>$data]);
    }

    public function getPending(){
        $data = 
        Tasks::leftJoin("cars","tasks.car_id", "=","cars.id")
                ->leftJoin("drivers", "tasks.driver_id", "=", "drivers.id")
                ->leftJoin("users as created_user", "tasks.created_by", "=", "created_user.id")
                ->leftJoin("users as canceled_user", "tasks.canceled_by", "=", "canceled_user.id")
                ->selectRaw("
                    tasks.*, 
                    concat(cars.name, ' - ', cars.license) as car_name, 
                    cars.license as car_license, 
                    drivers.name as driver_name,
                    created_user.name as created_name,
                    canceled_user.name as canceled_name,
                    IF(tasks.is_canceled = 1, 'CANCELED', 
                    IF(tasks.is_finished = 1, 'FINISHED',
                    IF(tasks.is_started = 1, 'STARTED',
                    IF(tasks.is_draft = 1, 'PENDING','OPEN')))) as status
                    ")
                ->where("tasks.is_draft","=",1)
                ->whereDate('task_date_start', '>=', date('Y-m-d'))
                ->orderBy("tasks.task_date_start", "desc")
                ->get();
        return response()->json(['data'=>$data]);
    }

    public function getHistory(){
        $data = 
        Tasks::leftJoin("cars","tasks.car_id", "=","cars.id")
                ->leftJoin("drivers", "tasks.driver_id", "=", "drivers.id")
                ->selectRaw("
                    tasks.*, 
                    concat(cars.name, ' - ', cars.license) as car_name, 
                    cars.license as car_license, 
                    drivers.name as driver_name,
                    IF(tasks.is_canceled = 1, 'CANCELED', 
                    IF(tasks.is_finished = 1, 'FINISHED',
                    IF(tasks.is_started = 1, 'STARTED',
                    IF(tasks.is_draft = 1, 'PENDING','OPEN')))) as status
                    ")
                ->whereDate('task_date_start', '<', date('Y-m-d'))
                ->orderBy("tasks.task_date_start", "desc")
                ->get();
        return response()->json(['data'=>$data]);
    }

    public function getPrepare(){
        $data = Tasks::where("is_started","0")
                ->whereDate('task_date_start', '=', date('Y-m-d'))
                ->where('task_date_start', '>', date('Y-m-d H:i:s'))
                ->where('task_date_start', '<', Carbon::now()->addMinute(15)->format('Y-m-d H:i:s'))
                ->orderBy("task_date_start", "asc")
                ->groupBy("driver_id")
                ->get();

        foreach($data as $d){
            $this->sendMessage(strtolower($d->driver->username), "Task akan segera dimulai", "Task dari " . $d->pic_name . " akan segera dimulai, silakan persiapkan diri Anda");
        }
        return response()->json(['data'=>$data]);
    }

    public function getOutdated(){
        $data = Tasks::where("is_started","0")
                ->whereDate('task_date_start', '=', date('Y-m-d'))
                ->where('task_date_start', '<', date('Y-m-d H:i:s'))
                ->orderBy("task_date_start", "asc")
                ->groupBy("driver_id")
                ->get();

        foreach($data as $d){
            $this->sendMessage(strtolower($d->driver->username), "Kamu belum memulai perjalanan", "Task kamu belum dikerjakan, silakan tekan tombol \"MULAI PERJALANAN\"");
        }
        return response()->json(['data'=>$data]);
    }

    public function destroy($id){
        $item = Tasks::findOrFail($id);
        $item->delete();

        return response()->json(['status'=>'berhasil hapus']);
    }

    // STORE TASK TO DATABASE
    public function store(Request $request){
        $params = $request->all();
        $driver = Drivers::find($params["driver_id"]);

        if($params["car_id"] == 0){
            $params["car_id"] = $driver->default_car_id;
        }

        $params["task_date_start"] = $params["date_start"] . " " . $params["hour_start"] . ":" . $params["minute_start"];
        $params["task_date_end"] =  $params["date_end"] . " " . $params["hour_end"] . ":" . $params["minute_end"];

        $params["is_started"] = 0;
        $params["is_finished"] = 0;
        $params["is_draft"] = 0;
        $params["approved_by"] = Auth::id();
        $params["created_by"] = Auth::id();
        
        $status = Tasks::create($params);
        if($status) {
            $this->sendMessage($driver->username, 'Task baru dari ' . $params["pic_name"], $params["task_description"] . ' ' . Carbon::parse($params["task_date_start"])->format("d F H:i - ") .  Carbon::parse($params["task_date_end"])->format("H:i"));

            return redirect('tasks/approved');
        }
        return response()->json(['result' => $params], 200);
    }

    // Show One Task
    public function show($id)
    {
        $data = 
        Tasks::leftJoin("cars","tasks.car_id", "=","cars.id")
                ->leftJoin("drivers", "tasks.driver_id", "=", "drivers.id")
                ->selectRaw("tasks.*, cars.id as car_id, cars.name as car_name, drivers.name as driver_name, drivers.id as driver_id,
                    IF(tasks.is_canceled = 1, 'CANCELED', 
                    IF(tasks.is_finished = 1, 'FINISHED',
                    IF(tasks.is_started = 1, 'STARTED',
                    IF(tasks.is_draft = 1, 'DRAFT','OPEN')))) as status
                    ")
                ->where("tasks.id","=",$id)
                ->get();
        return response()->json(['data'=>$data]);
    }

    public function edit($id)
    {
        $data['cars'] = Cars::all();
        $data['drivers'] = Drivers::all();
        $data['task_id'] = $id;
        return view('task/form')->with($data);
    }

    public function update(Request $request, $id){
        $params = $request->all();
        $task = Tasks::where('id', $id)->first();
        $task->update($params);
        return redirect('tasks');
    }

    public function approve(Request $request, $id) {
        $task = Tasks::where('id','=', $id)->first();
        $status = $task->update([ "is_draft" => $request->status, "approved_by" => Auth::id()]);
        if($status){
            $this->sendMessage($task->driver->username, 'Task baru dari ' . $task->pic_name, $task->task_description . ' ' . Carbon::parse($task->task_date_start)->format("d F H:i - ") .  Carbon::parse($task->task_date_end)->format("H:i"));

            return response()->json(['status'=>true], 200);
        }
        return response()->json(['status'=>false], 200);
    }

    public function aprove(Request $request, $id) {
        $task = Tasks::where('id','=', $id)->first();
        $status = $task->update([ "is_draft" => $request->status, "approved_by" => Auth::id()]);
        if($status)
            return response()->json(['status'=>true], 200);
        return response()->json(['status'=>false], 200);
    }

    public function cancel(Request $request, $id) {
        $task = Tasks::where('id','=', $id)->first();
        $status = $task->update([ "is_canceled" => $request->status, "canceled_by" => Auth::id()]);
        if($status)
            return response()->json(['status'=>true], 200);
        return response()->json(['status'=>false], 200);
    }

    function sendMessage($to, $title, $description){
		$heading = array(
			"en" => $title
			);
		$content = array(
			"en" => $description
			);
		
		$fields = array(
			'app_id' => env("ONESIGNAL_APP_ID"),
			'filters' => array(
                array("field" => "tag", "key" => "username", "relation" => "=", "value" => strtolower($to)),
            ),
			'data' => array("foo" => "bar"),
			'headings' => $heading,
            'contents' => $content,
            'small_icon' => 'src_assets_images_car',
            'android_accent_color' => '008db3'
        );
		
        $fields = json_encode($fields);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, env("ONESIGNAL_ENDPOINT_CREATE_NOTIFICATION"));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
												   'Authorization: Basic ' . env("ONESIGNAL_TOKEN")));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($ch);
        curl_close($ch);
        
		return $response;
	}
}