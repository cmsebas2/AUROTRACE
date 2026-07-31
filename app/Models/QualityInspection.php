<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditableTrait;

class QualityInspection extends Model
{
    use AuditableTrait;
    protected $guarded = [];
}
