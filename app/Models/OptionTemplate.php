<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionTemplate extends Model
{
    protected $table = 'option_templates';
    protected $fillable = [
        'name',
        'description',
        'price',
        'service_template_id',
    ];

    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'price' => 'float',
        'service_template_id' => 'integer',
    ];

    public static array $rules = [
        'name' => 'required|max:127',
        'description' => 'nullable',
        'price' => 'required|numeric|min:0.01',
        'service_template_id' => 'required|exists:service_templates,id'
    ];

    public function serviceTemplate(): BelongsTo
    {
        return $this->belongsTo(ServiceTemplate::class);
    }
}
