<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_name',
        'email',
        'phone_number',
        'import_errors',
        'is_duplicate',
        'duplicate_of',
        'import_batch'
    ];

    protected $casts = [
        'import_errors' => 'array',
        'is_duplicate' => 'boolean',
    ];

    public function originalRecord()
    {
        return $this->belongsTo(Company::class, 'duplicate_of', 'id');
    }

    public function duplicates()
    {
        return $this->hasMany(Company::class, 'duplicate_of', 'id');
    }
}
