<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailFotoStandarVariabel extends Model
{
    use HasFactory;
    protected $fillable = [
        'variabel_form_id',
        'image_path',
    ];

    public function variabelForm()
    {
        return $this->belongsTo(VariabelForm::class, 'variabel_form_id', 'id');
    }
}
