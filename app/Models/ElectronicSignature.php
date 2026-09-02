<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectronicSignature extends Model
{
    protected $table = 'electronic_signatures';

    protected $fillable = [
        'signable_type',
        'signable_id',
        'user_id',
        'second_user_id',
        'meaning',
        'hash_integridad',
        'signed_at',
        'ip_address'
    ];

    protected $casts = [
        'signed_at' => 'datetime'
    ];

    public function signable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function secondUser()
    {
        return $this->belongsTo(User::class, 'second_user_id');
    }
}
