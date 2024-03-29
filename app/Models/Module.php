<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

class Module extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function permissions(){
        $this->permissionClass = app(PermissionRegistrar::class)->getPermissionClass();

        return $this->hasMany($this->permissionClass);
    }
}
