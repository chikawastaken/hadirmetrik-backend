<?php
namespace App\Modules\Attendance\Infrastructure\Models;
use Illuminate\Database\Eloquent\Model;
class ClockRecord extends Model
{
    protected $table = 'clock_records';
    protected $fillable = ['user_id','date','clock_in','clock_out','status','source','device_id','idempotency_key','synced_at'];
    protected $casts = ['clock_in'=>'datetime','clock_out'=>'datetime','synced_at'=>'datetime'];
}
