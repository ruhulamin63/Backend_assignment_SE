<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources\CommonResource;
use Illuminate\Support\Facades\File;

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
        try {

            $category = Category::find($id);
            if($request->hasFile('image')){
                $category = $category->file;
                $image_path = public_path('uploads/category-image/' . $category);
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }

                $file = $request->file('image');
                $file_new_name = hexdec(uniqid()) . '_' . $file->getClientOriginalName();
                $directoryPath = public_path('uploads/category-image');
                File::makeDirectory($directoryPath, 0755, true, true);
                $file->move($directoryPath, $file_new_name);
            }



            if($request->hasFile('image')){
                $image = $request->file('image');
                $avatar_image_new_name = hexdec(uniqid()) . '_' . $image->getClientOriginalName();
                $image->move('uploads/category-image', $avatar_image_new_name);
                $img = 'uploads/category-image/' . $avatar_image_new_name;
            } else {
                $img = $category->image;
            }

            $category->update([
                'name' => $request->name,
                'image' => $img,
                'updated_by' => auth()->user()->id,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Category updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $category = Category::find($id);
            $category->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
