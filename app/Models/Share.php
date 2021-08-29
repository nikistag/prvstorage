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
        'expiration',
        'status',

    ];

    //Relationships

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //Getters
    public function getExpirationDateAttribute()
    {
        return $this->expiration == null ? null : date('Y-m-d H:i:s', $this->expiration);
    }
}
