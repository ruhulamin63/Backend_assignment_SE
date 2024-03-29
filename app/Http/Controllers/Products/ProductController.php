<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CategoryProduct;
use App\Models\Category;
use App\Http\Resources\CommonResource;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req){
        try {
            $categories = Category::with(['products' => function ($query) use ($req) {
                if ($req->search) {
                    $query->where('name', 'like', '%' . $req->search . '%');
                };
            }]);

            if ($req->search) {
                $categories->where('name', 'like', '%' . $req->search . '%');
            }

            if($req->category_id){
                $categories->where('id', $req->category_id);
            }
            $categories = $categories->latest()->paginate($req->rows);
    
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
    public function store(Request $request){
        try {
            if($request->hasFile('image')){
                $image = $request->file('image');
                $avatar_image_new_name = hexdec(uniqid()) . '_' . $image->getClientOriginalName();
                $image->move('uploads/product-image', $avatar_image_new_name);
                $img = 'uploads/product-image/' . $avatar_image_new_name;
            }

            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $img,
                'user_id' => auth()->user()->id
            ]);
            // $product->categories()->attach($request->category_id);
            CategoryProduct::create([
                'category_id' => $request->category_id,
                'product_id' => $product->id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Product created successfully'
            ], 201);

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
            $product = Product::find($id);
            return response()->json($product);
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
        try{
            $product = Product::find($id);
            if($request->hasFile('image')){
                $image = $request->file('image');
                $avatar_image_new_name = hexdec(uniqid()) . '_' . $image->getClientOriginalName();
                $image->move('uploads/product-image', $avatar_image_new_name);
                $img = 'uploads/product-image/' . $avatar_image_new_name;
            } else {
                $img = $product->image;
            }

            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'image' => $img,
                'user_id' => auth()->user()->id
            ]);
            // $product->categories()->sync($request->category_id);
            CategoryProduct::where('product_id', $id)->update([
                'category_id' => $request->category_id
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Product updated successfully'
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
        try {
            $product = Product::find($id);
            $product->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
