<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sample extends Model { protected $fillable=['order_id','sample_no','description','gemstone_type','colour','weight','pieces','condition_note','status','image_path'];
public function order(){return $this->belongsTo(Order::class);} public function tests(){return $this->hasMany(Test::class);} public function reports(){return $this->hasMany(Report::class);}
}
