<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    use HasFactory;

    protected $table = "periode";
    protected $primaryKey = "id";
    public $incrementing = "true";
    
    public $timestamps = "true";
    protected $fillable = [
        "tahun",
        "bulan",
    ];
}
