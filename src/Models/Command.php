<?php

namespace LaravelCode\EventSourcing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LaravelCode\EventSourcing\Models\Command.
 *
 * @property int $id
 * @property string $class
 * @property string $payload
 * @property string $status
 * @property string|null $author_id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Command paginatedResources(\Illuminate\Http\Request $request, $withQuery)
 * @method static \Illuminate\Database\Eloquent\Builder|Command resource($modelId, \Illuminate\Http\Request $request, $withQuery = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Command viewResource($modelId, \Illuminate\Http\Request $request, $withQuery = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Command newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Command newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Command query()
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Command whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Command extends Model
{
    use SearchBehaviourTrait;

    const STATUS_RECEIVED = 'received';
    const STATUS_HANDLED = 'handled';
    const STATUS_FAILED = 'failed';

    protected $casts = [
        'id' => 'string',
    ];

    protected $includes = [
        'error',
        'events',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'class',
        'payload',
        'status',
        'author_id',
        'key',
    ];

    public function error()
    {
        return $this->hasOne(CommandError::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
