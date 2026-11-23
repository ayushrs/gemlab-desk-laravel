<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Test extends Model { protected $fillable=['sample_id','method','observations','result','tested_by','tested_at'];
public function sample(){return $this->belongsTo(Sample::class);}
}
