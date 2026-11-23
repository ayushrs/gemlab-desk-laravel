<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model { protected $fillable=['order_no','customer_id','service','priority','status','payment_status','amount','notes','received_at'];
public function customer(){return $this->belongsTo(Customer::class);} public function samples(){return $this->hasMany(Sample::class);} public function payments(){return $this->hasMany(Payment::class);}
}
