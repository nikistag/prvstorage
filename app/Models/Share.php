<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'path',
        'code',
        'type',
        'composition',
        'storage',
        'expiration',
        'status',
        'unlimited',


    ];

    //Relationships

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //Getters
    public function getExpirationDateAttribute()
    {
        return $this->expiration == null ? null : date('M d, Y', $this->expiration);
    }
    public function getDownloadTypeAttribute()
    {
        return $this->unlimited == 1 ? "Multiple" : "One time";
    }
    public function getLinkAttribute()
    {
        return route('share.download', ['code' => $this->code]);
    }
    public function getShortLinkAttribute()
    {
        if(strlen($this->link) > 30 ){
            return substr($this->link, 0, 25) . "*~";
        }else{
            return $this->link;
        }

    }
    public function getReadableStorageAttribute()
    {
        $file_size = ['size' => round($this->storage, 2), 'type' => 'bytes'];
        if ($file_size['size'] > 1000) {
            $file_size = ['size' => round($file_size['size'] / 1024, 2), 'type' => 'Kb'];
        } else {
            return $file_size;
        }
        if ($file_size['size'] > 1000) {
            $file_size = ['size' => round($file_size['size'] / 1024, 2), 'type' => 'Mb'];
        } else {
            return $file_size;
        }
        if ($file_size['size'] > 1000) {
            $file_size = ['size' => round($file_size['size'] / 1024, 2), 'type' => 'Gb'];
        } else {
            return $file_size;
        }
        return $file_size;
    }

}
