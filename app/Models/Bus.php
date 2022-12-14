<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Bus extends Model
{
    use HasFactory, Sortable;

    // Nếu không dùng $id thì phải khai báo $primaryKey
    // Nếu $primaryKey không phải số thì phải khai báo $keyType
    protected $primaryKey = 'IdXe';
    protected $keyType = 'string';


    /* Laravel prevent you from hijacker 
    by accidentally setting new value on fields you do not want to change
    
    You have to define which fields you want to change in the model
    */
    protected $fillable = [
        'IdXe',
        'So_xe',
        'IdNX',
        'Doi_xe',
        'Loai_xe',
        'So_Cho_Ngoi',
    ];

    public $sortable = [
        'IdXe',
        'So_xe',
        'IdNX',
        'Doi_xe',
        'Loai_xe',
        'So_Cho_Ngoi',
        
    ];

    public function busCompany()
    {
        return $this->belongsTo(BusCompany::class, 'IdNX', 'IdNX');
    }
}
