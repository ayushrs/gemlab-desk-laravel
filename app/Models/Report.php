<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Report extends Model { protected $fillable=['sample_id','report_no','conclusion','treatment','origin','issued_at','approved_by','status','version'];
public function sample(){return $this->belongsTo(Sample::class);}
}
