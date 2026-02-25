<?php
/*
 * File name: Report.php
 * Last modified: 2026.02.023 at 16:53:44
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class Report
 * @package App\Models
 * @version January 19, 2026, 03:59 pm UTC
 *
 * @property string caption
 * @property integer salon_id
 * @property integer author_id
 */
class Report extends Model
{
    use HasFactory;

    public $fillable = [
        'post_id',
        'model_id',
        'model_type',
    ];

    public $table = 'reports';
    
    /**
     * Validation rules
     *
     * @var array
     */
    public static function rules(): array
    {
        return [
            
            'model' => 'required|string',  
            'model_id' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $modelName = request()->input('model');

                    // 🔑 clé : récupération sécurisée via morphMap
                    $modelClass = Relation::getMorphedModel($modelName);

                    if (!$modelClass) {
                        $fail('Invalid model.');
                        return;
                    }

                    if (!$modelClass::where('id', $value)->exists()) {
                        $fail('The selected model_id does not exist.');
                    }
                },
            ],
        ];
    }

    protected $hidden = [
        "created_at",
        "updated_at",
    ];


    public function model()
    {
        return $this->morphTo();
    }



    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

   
}
