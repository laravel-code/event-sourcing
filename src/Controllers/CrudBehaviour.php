<?php

namespace LaravelCode\EventSourcing\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelCode\EventSourcing\Exceptions\ModelNotFoundException;
use LaravelCode\EventSourcing\Helpers\ModelFinder;

trait CrudBehaviour
{
    public string $model;
    public string $crudController;
    protected array $namespacePrefix = [
        'models' => 'App\Models',
    ];

    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        if (! isset($this->model)) {
            $this->model = ModelFinder::extract(get_called_class(), $this->namespacePrefix['models']);
            if (! isset($this->model)) {
                throw new ModelNotFoundException();
            }
        }
    }

    public function __call($method, $args)
    {
        switch ($method) {
            case'index':
                $this->_index();
                break;
            case'show':
                $this->_show();
                break;
            default:

        }
    }

    private function _index(): void
    {
        /** @var LengthAwarePaginator $result */
        $result = call_user_func([$this->model, 'paginatedResources'], $this->request, function (Builder $query) {
            $this->beforeSearchAndFind($query);
        });

        response()->json($result)->send();
    }

    private function _show(): void
    {
        /** @var LengthAwarePaginator $result */
        $result = call_user_func([$this->model, 'resource'], $this->request->id, $this->request, function (Builder $query) {
            $this->beforeSearchAndFind($query);
        });

        response()->json($result)->send();
    }

    public function beforeSearchAndFind(Builder $query)
    {
    }
}
