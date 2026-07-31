<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProcess extends Model
{
    protected $fillable = ['process_id', 'name'];

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
