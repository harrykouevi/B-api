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
        'group',
        'description',
        'price',
        'service_template_id',
        'option_group_id'
    ];

    protected $casts = [
        'name' => 'string',
        'group' => 'string',
        'description' => 'string',
        'price' => 'float',
        'service_template_id' => 'integer',
        'option_group_id' => 'integer'
    ];

    public static array $rules = [
        'name' => 'required|max:127',
        'name' => 'required|max:127',
        'description' => 'nullable',
        'price' => 'required|numeric|min:0.01',
        'service_template_id' => 'required|exists:service_templates,id',
        'option_group_id' => 'nullable|exists:option_groups,id'
        
    ];

    public function serviceTemplate(): BelongsTo
    {
        return $this->belongsTo(ServiceTemplate::class);
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }

     /**
     * @return BelongsTo
     **/
    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id', 'id');
    }
}
