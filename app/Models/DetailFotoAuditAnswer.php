<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailFotoAuditAnswer extends Model
{
    use HasFactory;

    protected $table = 'detail_foto_audit_answers';

    protected $fillable = [
        'detail_audit_answer_id',
        'image_path',
    ];

    public function detailAuditAnswer()
    {
        return $this->belongsTo(DetailAuditAnswer::class, 'detail_audit_answer_id', 'id');
    }
}
