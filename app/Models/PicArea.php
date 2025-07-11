<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PicArea extends Model
{
    use HasFactory;
    protected $table = 'pic_areas';

    protected $fillable = [
        'pic_id',
    ];

    public function auditAnswer()
    {
        return $this->hasMany(AuditAnswer::class, 'pic_area', 'id');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'pic_id', 'emp_id');
    }
}
