<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariabelForm extends Model
{
    use HasFactory;

    protected $table = 'variabel_forms';

    protected $fillable = [
        'tema_form_id',
        'variabel',
        'standar_variabel',
        'standar_foto',
    ];

    public function temaForm()
    {
        return $this->belongsTo(TemaForm::class, 'tema_form_id', 'id');
    }

    public function detailAuditAnswers()
    {
        return $this->hasMany(DetailAuditAnswer::class, 'variabel_form_id', 'id');
    }

    public function detailFotoStandarVariabels()
    {
        return $this->hasMany(DetailFotoStandarVariabel::class, 'variabel_form_id', 'id');
    }
}
