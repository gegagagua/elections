<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    public const STATUS_NOT_CALLED = 'not_called';
    public const STATUS_CALLED = 'called';
    public const STATUS_CAME = 'came';

    protected $fillable = [
        'admin_id',
        'district_id',
        'first_name',
        'last_name',
        'personal_id',
        'address',
        'phone',
        'image_path',
        'status',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
