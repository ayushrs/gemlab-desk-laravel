<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model { protected $fillable=['code','name','company','email','phone','gst','address'];
public function orders(){return $this->hasMany(Order::class);}
}
