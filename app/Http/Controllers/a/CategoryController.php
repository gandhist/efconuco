<?php

namespace App\Http\Controllers;
use App\Category;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data['category'] = Category::all();
        return view('category/index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('category/create');
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
            'name'=> 'required'
        ]);
         $category = new Category();
         $category->name=$request->get('name');
         $category->description=$request->get('description');
         $category->created_by = Auth::id();
         $category->created_at = Carbon::now()->toDateTimeString();
         $category->save();
         return redirect('/category')->with('success', 'Data berhasil ditambahkan');
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
    public function edit(category $category,$id)
    {
        //
        $data['category'] = $category::find($id);
        return view('category/edit')->with($data);
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
            'name'=> 'required'
        ]);
         $category = Category::find($id);
         $category->name=$request->get('name');
         $category->description=$request->get('description');
         $category->updated_by = Auth::id();
         $category->updated_at = Carbon::now()->toDateTimeString();
         $category->save();
         return redirect('/category')->with('success', 'Data berhasil Update');
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
        $category= Category::find($id);
        $category->deleted_by = Auth::id();
        $category->deleted_at = Carbon::now()->toDateTimeString();
        if($category->save()){
            return response()->json([
                'success' => 'Beban berhasil dihapus'
            ]);
        }
    }
}
