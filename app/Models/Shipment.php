<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Shipment extends Model { protected $fillable=['order_id','courier','tracking_no','dispatch_date','delivery_status','notes'];
public function order(){return $this->belongsTo(Order::class);}
}
