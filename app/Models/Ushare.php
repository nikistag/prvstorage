<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ushare extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'wuser_id',
        'path',
        'expiration',   
    ];

    //Relationships

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wuser()
    {
        return $this->belongsTo(User::class, 'wuser_id');
    }

    //Getters
    public function getExpirationDateAttribute()
    {
        return $this->expiration == null ? null : date('M d, Y', $this->expiration);
    }

    public function getExpiredAttribute()
    {
        if($this->expiration <= time()){
            return true;
        }else{
            return false;
        }
    }

    public function getShortPathAttribute()
    {
        $userlen = strlen("/" . $this->user->name);
        return substr($this->path, $userlen, strlen($this->path));       
    }
   
    public function getCurrentFolderAttribute()
    {
        return substr($this->shortPath, 0, strripos($this->shortPath, "/"));        
    }

 

/*     public function getLinkAttribute()
    {
        return route('share.download', ['code' => $this->code]);
    }
     */
/*     public function getShortLinkAttribute()
    {
        if (strlen($this->link) > 30) {
            return substr($this->link, 0, 25) . "*~";
        } else {
            return $this->link;
        }
    }
 */
}
