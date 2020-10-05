<?php

namespace LaravelCode\EventSourcing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LaravelCode\EventSourcing\Models\CommandError.
 *
 * @property int $id
 * @property string $command_id
 * @property string $class
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError query()
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError whereCommandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CommandError whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CommandError extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'command_id',
        'class',
        'message',
    ];
}
