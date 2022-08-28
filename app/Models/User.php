<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
//use Illuminate\Auth\Passwords\CanResetPassword;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin',
        'active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //Getters
    public function getAdminRoleAttribute()
    {
        return $this->admin === 1 ? 'Yes' : 'No';
    }
    public function getActiveStatusAttribute()
    {
        return $this->active === 1 ? 'Yes' : 'No';
    }

    public function getFolderSizeAttribute()
    {
        $allFiles = Storage::allFiles("/".$this->name);
        $thisFolderSize = 0;
        foreach ($allFiles as $file) {
            $thisFolderSize += File::size(Storage::path($file));
        }
        $folderSize = ['size' => round($thisFolderSize, 2), 'type' => 'bytes', 'byteSize' => round($thisFolderSize, 2)];
        if ($folderSize['size'] > 1000) {
            $folderSize = ['size' => round($folderSize['size'] / 1024, 2), 'type' => 'Kb', 'byteSize' => round($thisFolderSize, 2)];
        } else {
            return $folderSize;
        }
        if ($folderSize['size'] > 1000) {
            $folderSize = ['size' => round($folderSize['size'] / 1024, 2), 'type' => 'Mb', 'byteSize' => round($thisFolderSize, 2)];
        } else {
            return $folderSize;
        }
        if ($folderSize['size'] > 1000) {
            $folderSize = ['size' => round($folderSize['size'] / 1024, 2), 'type' => 'Gb', 'byteSize' => round($thisFolderSize, 2)];
        } else {
            return $folderSize;
        }
        return $folderSize;
    }


    //Relationships

    public function shares()
    {
        return $this->hasMany(Share::class);
    }
}
