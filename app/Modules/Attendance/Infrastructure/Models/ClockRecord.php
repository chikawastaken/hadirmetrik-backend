<?php
namespace App\Modules\Attendance\Infrastructure\Models;
use Illuminate\Database\Eloquent\Model;
class ClockRecord extends Model
{
    protected $table = 'clock_records';
    protected $fillable = ['user_id','date','clock_in','clock_out','status'];
    protected $casts = ['clock_in'=>'datetime','clock_out'=>'datetime'];
}
