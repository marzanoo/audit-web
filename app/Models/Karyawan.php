<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    // protected $connection = 'mysql_hris';
    // protected $table = 'pcx_emp_info_view';
    protected $table = 'karyawans';
    protected $fillable = ['emp_id', 'emp_name', 'dept', 'email'];
    protected $primaryKey = 'emp_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // public function area()
    // {
    //     return $this->hasOne(Area::class, 'emp_id', 'pic_area');
    // }

    public function auditAnswer()
    {
        return $this->hasMany(PicArea::class, 'pic_id', 'emp_id');
    }
    public function detailAuditeeAnswer()
    {
        return $this->hasMany(DetailAuditeeAnswer::class, 'auditee', 'emp_id');
    }
}
