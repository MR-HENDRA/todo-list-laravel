<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'description', 'date', 'completed'];

    // Opsional: cast kolom completed ke boolean
    protected $casts = [
        'completed' => 'boolean',
        'date' => 'date',
    ];
}
