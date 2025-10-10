<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTemplate extends Model
{
    use HasFactory;

    protected $table = 'service_templates';
    protected $fillable = [
        'name',
        'description',
        'category_id'
    ];

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'category_id' => 'integer'
    ];

    /**
     * Validation rules
     */
    public static array $rules = [
        'name' => 'required|max:127',
        'description' => 'required',
        'category_id' => 'required|exists:categories,id'
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
