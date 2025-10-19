<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class OptionTemplate extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as protected getFirstMediaUrlTrait;
    }
    // use HasTranslations;
    use HasFactory;

    public $table = 'option_templates';
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

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }
}
