<?php

namespace LaravelCode\EventSourcing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * LaravelCode\EventSourcing\Models\Event.
 *
 * @property int $id
 * @property string $command_id
 * @property string $resource_id
 * @property string $model
 * @property string $class
 * @property string $payload
 * @property int $revision_number
 * @property string|null $author_id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Event paginatedResources(\Illuminate\Http\Request $request, $withQuery)
 * @method static \Illuminate\Database\Eloquent\Builder|Event resource($modelId, \Illuminate\Http\Request $request, $withQuery = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Event viewResource($modelId, \Illuminate\Http\Request $request, $withQuery = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCommandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereRevisionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Event extends Model
{
    use SearchBehaviourTrait;

    protected $casts = [
        'id' => 'string',
        'command_id' => 'string',
    ];

    protected $fillable = [
        'id',
        'model',
        'command_id',
        'resource_id',
        'payload',
        'revision_number',
        'key',
        'class',
        'author_id',
    ];

    protected $includes = [
        'command',
        'command.error',
    ];

    protected $orderFields = [
        'revision_number',
        'model',
        'created_at',
        'updated_at',
    ];

    protected function search()
    {
        return [
            'id',
            'resource_id',
            'model' => function (Builder $query, $value) {
                if (class_exists($value)) {
                    return $query->where('model', $value);
                }

                return $query->where('model', 'App\\Models\\'.$value);
            },
            'class' => function (Builder $query, $value) {
                if (class_exists($value)) {
                    return $query->where('class', $value);
                }

                return $query->where('class', 'App\\Events\\Apply\\'.$value);
            },
            'command_id',
            'author_id',
        ];
    }

    public function command()
    {
        return $this->belongsTo(Command::class);
    }
}
