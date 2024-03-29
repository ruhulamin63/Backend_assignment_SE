<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogPreference;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogPreference;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
    ];

    /**
     * Get the post that owns the comment.
     */
    public $appends = ["photo_url", 'display_name'];

    public function getPhotoUrlAttribute(){
        $image = $this->attributes['photo'] ?? '/avatar5.png';
        return asset($image);
    }

    public function getDisplayNameAttribute(){
        if(isset($this->attributes['name']) && isset($this->attributes['phone']) && isset($this->attributes['nid'])){
            return $this->attributes['name']. ' - '. $this->attributes['phone']. ' - '. $this->attributes['nid'];
        }else{
            return '';
        }
    }

    public function user_details() : BelongsTo {
        return $this->belongsTo(UserDetails::class);
    }

    public function user_enrolls() : BelongsToMany {
        return $this->belongsToMany(UserEnroll::class);
    }

    public function sector(): BelongsTo {
        return $this->belongsTo(Sector::class);
    }

    public function sectors() : BelongsToMany {
        return $this->belongsToMany(Sector::class);
    }

    public function occupation(): BelongsTo {
        return $this->belongsTo(Occupation::class);
    }

    public function occupations() : BelongsToMany {
        return $this->belongsToMany(Occupation::class);
    }

    public function level(): BelongsTo {
        return $this->belongsTo(Level::class);
    }

    public function levels() : BelongsToMany {
        return $this->belongsToMany(Level::class);
    }

    public function stage(): BelongsTo {
        return $this->belongsTo(Stage::class);
    }

//    public function panel(): BelongsTo {
//        return $this->belongsTo(Panel::class);
//    }

    public function division(): BelongsTo {
        return $this->belongsTo(Division::class);
    }

    public function divisions() : BelongsToMany {
        return $this->belongsToMany(Division::class);
    }

    public function district(): BelongsTo {
        return $this->belongsTo(District::class);
    }
    public function districts() : BelongsToMany {
        return $this->belongsToMany(District::class);
    }

    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    public function transactions() : BelongsToMany {
        return $this->belongsToMany(Transaction::class);
    }

    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    public function countries(): BelongsToMany {
        return $this->belongsToMany(Country::class);
    }

    public function city(): BelongsTo {
        return $this->belongsTo(City::class);
    }

    public function cities() : BelongsToMany{
        return $this->belongsToMany(City::class);
    }
}
