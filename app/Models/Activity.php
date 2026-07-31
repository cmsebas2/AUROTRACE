<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['sub_process_id', 'name', 'status_key'];

    public function subProcess()
    {
        return $this->belongsTo(SubProcess::class);
    }
}
