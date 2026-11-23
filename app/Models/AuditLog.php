<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model { protected $fillable=['user_id','username','action','entity','entity_id','details'];
}
