<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\CommonResource;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req) {
        try {
            $categories = Category::latest();

            if($req->search){
                $categories->where('name', 'like', '%' . $req->search . '%');
            }
            $categories = $categories->paginate($req->rows);
            
            return CommonResource::collection($categories);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            if($request->hasFile('image')){
                $image = $request->file('image');
                $avatar_image_new_name = hexdec(uniqid()) . '_' . $image->getClientOriginalName();
                $image->move('uploads/category-image', $avatar_image_new_name);
                $img = 'uploads/category-image/' . $avatar_image_new_name;
            }

            $category = Category::create([
                'name' => $request->name,
                'image' => $img,
                'created_by' => auth()->user()->id,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Category created successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $category = Category::find($id);
            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    //all categories
    public function allCategories(){
        try{
            $categories = Category::all();
            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
