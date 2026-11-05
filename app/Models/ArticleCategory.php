<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    protected $fillable = ['slug', 'name', 'sort_order'];

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
