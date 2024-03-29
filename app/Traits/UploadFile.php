<?php

namespace App\Traits;


use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

trait UploadTrait{

    public $public_path = "/public/";
    public $storage_path = "/storage/";

    public function ImageUpload( $file, $path, $width, $height)
    {
        if ($file){
            $extension       = $file->getClientOriginalExtension();
            $file_name       = time().'.'.$extension;
            $url             = $file->storeAs($this->public_path.$path,$file_name);
            $public_path     = public_path($this->storage_path.$path.'/'.$file_name);
            $img             = Image::make($public_path)->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $url             = preg_replace( "/public\//", "", $url );
            return $img->save($public_path) ? $url : '';
        }
    }

    public function FileUpload( $file, $path)
    {
        if ($file){
            $extension       = $file->getClientOriginalExtension();
            $file_name       = time().'.'.$extension;
            $url             = $file->storeAs($this->public_path.$path,$file_name);
            $public_path     = public_path($this->storage_path.$path.'/'.$file_name);
            $url             = preg_replace( "/public\//", "", $url );
            return $url;
        }
    }

}
