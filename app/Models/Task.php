<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'description', 'date', 'completed', 'created_time', 'created_date'];

    protected $casts = [
        'completed' => 'boolean',
        'date' => 'date',
        'created_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            $task->created_time = now()->format('H:i:s');
            $task->created_date = now()->toDateString();
        });
    }

    public function getWhatsAppTimeAttribute()
    {
        if (!$this->created_time || !$this->created_date) {
            return '';
        }

        $createdDate = \Carbon\Carbon::parse($this->created_date);
        $time = \Carbon\Carbon::parse($this->created_time)->format('H:i');

        if ($createdDate->isToday()) {
            return "Today, " . $time;
        } elseif ($createdDate->isYesterday()) {
            return "Yesterday, " . $time;
        } else {
            return $createdDate->format('M j') . ", " . $time;
        }
    }

    public function getSimpleTimeAttribute()
    {
        return $this->created_time ? \Carbon\Carbon::parse($this->created_time)->format('H:i') : '';
    }
}
