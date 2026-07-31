<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $fillable = ['macro_process_id', 'name'];

    public function macroProcess()
    {
        return $this->belongsTo(MacroProcess::class);
    }

    public function subProcesses()
    {
        return $this->hasMany(SubProcess::class);
    }
}
