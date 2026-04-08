<?php

namespace Jackwander\ModuleMaker\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;

class BaseModel extends Model
{
    use HasUuids;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function scopeWithHas(Builder $query, string $relation, \Closure $constraint): Builder
    {
        return $query->whereHas($relation, $constraint)
                     ->with([$relation => $constraint]);
    }

    public function getImageAddressAttribute(): ?string
    {
        if ($this->image && Storage::exists('public/' . str_replace('storage/', '', $this->image))) {
            return asset($this->image);
        }
        return null;
    }
}
