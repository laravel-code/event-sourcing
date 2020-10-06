# Event sourcing for laravel 7/8

It is all in the name, event sourcing from laravel 7/8.
This package will work with Laravel and will use the events and listeners already present in laraval.

## Installation

```
composer require laravel-code/event-sourcing
```

## Setup

Running the migration will create two tables ```commands``` and ``events``

Create migrations for the tables that will be event sourced with an extra column ``revison_number``

```php
 $table->integer('revision_number');
```

Then just run all migrations.

```
php artisan migrate
```
## Api

### Trait LaravelCode\EventSourcing\Models\SearchBehaviourTrait

```SearchBehaviourTrait``` will take params from the ```Request``` and tries apply them to the model.

#### Defining search 


```php
<?php
use LaravelCode\EventSourcing\Models\SearchBehaviourTrait;

...
class Post extends Model
{
    use SearchBehaviourTrait;
    
    public function search()
    {
        return [
            'published',
            'q' => 'finder',
            'withActiveUser' => 'findWithActiveUser',
        ];
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'post_user');
    }

    public function findWithActiveUser(Builder $query, $value)
    {
        $query->whereHas('user', function (Builder $query) use ($value) {
            $query->where('active', boolval($value));
        });
    }

    public function finder(Builder $query, $value)
    {
        return $query->where('name', 'LIKE', '%'.$value.'%');
    }

}
```

The public function ```search```  will return an ``Array`` with value column name or key value pair 
where key is the column name and value the name of the callback.

If only the column name is specified, a exact match search is applied.
```php
$query->where($column, $value);
```

Withing the callback function you will have ```Builder``` and the param ``value`` from the ``Request`` availalbe.

```php
public function finder(Builder $query, $value)
```

#### Post.php
```php
<?php
namespace App\Models;

use LaravelCode\EventSourcing\Models\SearchBehaviourTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, SearchBehaviourTrait;

    public function search()
    {
        return [
            'published',
            'q' => 'finder',
            'withActiveUser' => 'findWithActiveUser',
        ];
    }

    /**
     * Only return posts where the user is active or not.
     *
     * @param Builder $query
     * @param $value
     */
    public function findWithActiveUser(Builder $query, $value)
    {
        $query->whereHas('user', function (Builder $query) use ($value) {
            $query->where('active', boolval($value));
        });
    }

    public function finder(Builder $query, $value)
    {
        return $query->where('name', 'LIKE', '%'.$value.'%');
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'post_user');
    }
}
```


### Setting up controller

The controller will not contain much code, 
the handling of what to do will be done in the model ``SearchBehaviour`` and ```Listeners```.

As you can see below the controller does not have any logic in it, 
but still has enough for you to follow the code.

You are still able to apply ``policies`` and ```RequestValidation```.

As you can see instead of ```event(new PostCreate(null, $request))``` you must call ```::handleEvent``` on the event class.

``handleEvent`` accepts two params, ``id`` of the entity and the ``Collection`` containing all params.

When creating a new entity ``id`` can be ```null```
 
#### PostController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Events\PostCreate;
use App\Events\PostDelete;
use App\Events\PostUpdate;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\WickedQuery\Controllers\CrudBehaviour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use CrudBehaviour;

    public function index(Request $request) {
        return Post::paginatedResources($request, function() {});
    }

    public function show(Request $request, Post $post) {
        return Post::viewResource($post->id, $request, function() {});
    }

    public function create(Request $request)
    {
        PostCreate::handleEvent(null, $request->all());
    }

    public function update(Request $request, $id)
    {
        PostUpdate::handleEvent($id, $request->all());
    }

    public function delete(Request $request, $id)
    {
        PostDelete::handleEvent($id, $request->all());
    }
}
```

### Events

Events should implement ``implements LaravelCode\EventSourcing\Contracts\CommandEventInterface`` and ``use StoreCommandEvent``

```php
<?php

namespace App\Events;

use App\WickedQuery\Contracts\CommandEventInterface;
use App\WickedQuery\EventSourcing\StoreCommandEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PostCreate implements CommandEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels, StoreCommandEvent;

    /**
     * @var null
     */
    private $postId;
    private string $name;

    /**
     * Create a new event instance.
     *
     * @param $id
     * @param string $name
     */
    public function __construct($id, string $name)
    {
        $this->postId = $id;
        $this->name = $name;
    }

    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param $id
     * @param Collection $collection
     * @return static
     */
    public static function fromPayload($id, Collection $collection): self
    {
        return new self(
            $id,
            $collection->get('name')
        );
    }

    /**
     * @return string[]
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->getPostId(),
            'name' => $this->getName(),
        ];
    }
}

```

## Listeners

Within ```Events/EventsProvider``` ``` shouldDiscoverEvents()``` should be disabled (return false).

Subscribe your listeners
```php
    protected $subscribe = [
        PostCreateListener::class,
    ]
```


```php

<?php

namespace App\Listeners;

use App\Events\PostCreate;
use App\Models\Events\Post\PostWasCreated;
use App\Models\Post;
use LaravelCode\EventSourcing\Listeners\ApplyListener;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class PostCreateListener
 * @package App\Listeners
 *
 * @property Post $entity
 */
class PostCreateListener extends BasePostListener implements ShouldQueue
{
    use ApplyListener;

    /**
     * Handle the event.
     *
     * @param PostCreate $event
     * @return void
     */
    public function handleCommand(PostCreate $event)
    {
        $this->event(new PostWasCreated($event->getPostId(), $event->getName()));
    }

    public function applyPostWasCreated(PostWasCreated $event)
    {
        $this->entity->name = $event->getName();
        $this->entity->body = $event->getName();
        $this->entity->published = false;
    }
}

```


### PostWasCreated
```php
<?php

namespace App\Models\Events\Post;

use LaravelCode\EventSourcing\Contracts\EventInterface;
use LaravelCode\EventSourcing\EventSourcing\Event;
use Illuminate\Support\Collection;

class PostWasCreated extends Event implements EventInterface
{
    private string $name;

    /**
     * PostWasCreated constructor.
     * @param $id
     * @param string $name
     */
    public function __construct($id, string $name)
    {
        parent::__construct($id);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public static function fromPayload($id, Collection $collection)
    {
        return new self($id, $collection->get('name'));
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
```


        




