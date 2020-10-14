<?php

namespace LaravelCode\EventSourcing\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait SearchBehaviourTrait
{
    /**
     * @param Builder $query
     * @param Request $request
     * @param $withQuery
     */
    public function scopePaginatedResources(Builder $query, Request $request, $withQuery = null): LengthAwarePaginator
    {
        $this->runIncludes($query, $request->get('include', null));
        $this->runSearch($query, $request->all());
        $this->runSort(
            $query,
            $request->get('order_field', null),
            $request->get('order_dir', null)
        );

        $perPage = $request->get('limit') ?: $this->getPerPage();
        if ($perPage > 100) {
            $perPage = $this->getPerPage();
        }
        if ($withQuery) {
            return $query->tap($withQuery)->paginate($perPage);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param Builder $query
     * @param string|null $includes
     * @return Builder
     */
    private function runIncludes(Builder $query, string $includes = null): Builder
    {
        $list = $this->parseInclude($includes);
        if ($list) {
            $query->with($list);
        }

        return $query;
    }

    /**
     * @param $includes
     * @return array|void
     */
    private function parseInclude($includes)
    {
        if (! $includes) {
            return;
        }

        if ($includes && ! isset($this->includes)) {
            return explode(',', $includes);
        }

        $list = explode(',', $includes);
        foreach ($list as $key => $item) {
            if (! in_array($item, $this->includes)) {
                throw new \InvalidArgumentException('Include `'.$item.'` is not configured on the model');
            }
        }
        if (count($list)) {
            return $list;
        }
    }

    /**
     * @param Builder $query
     * @param array $params
     * @return Builder
     */
    private function runSearch(Builder $query, array $params): Builder
    {
        if (! method_exists($this, 'search')) {
            return $query;
        }

        foreach ($this->search() as $key => $column) {
            if (is_numeric($key) && isset($params[$column])) {
                $this->filterFullMatch($query, $column, $params[$column]);
                continue;
            }

            if (! isset($params[$key])) {
                continue;
            }

            if (is_callable($column)) {
                $column($query, $params[$key], $params);
            }

            if (is_string($column)) {
                call_user_func([$this, $column], $query, $params[$key], $params);
            }
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string $column
     * @param string $value
     * @return Builder
     */
    private function filterFullMatch(Builder $query, string $column, string $value)
    {
        return $query->where($column, $value);
    }

    /**
     * @param Builder $query
     * @param string|null $column
     * @param string|null $orderDir
     * @return Builder
     */
    private function runSort(Builder $query, string $column = null, string $orderDir = null): Builder
    {
        if (! isset($this->orderFields) || ! $column) {
            return $query;
        }

        if (! in_array($orderDir, ['asc', 'desc'])) {
            $orderDir = 'asc';
        }

        if (in_array($column, $this->orderFields)) {
            $query->orderBy($column, $orderDir);
        }

        return $query;
    }

    /**
     * Load and send resource.
     *
     * @param Builder $query
     * @param int|string $modelId
     * @param Request $request
     * @param $withQuery
     */
    public function scopeViewResource(Builder $query, $modelId, Request $request, $withQuery = null)
    {
        $resource = $this->scopeResource($query, $modelId, $request, $withQuery)->firstOrFail();
        \Response::json($resource)
            ->setStatusCode(200)
            ->send();
    }

    /**
     * Load the resource.
     *
     * @param Builder $query
     * @param int|string $modelId
     * @param Request $request
     * @param $withQuery
     * @return Builder
     */
    public function scopeResource(Builder $query, $modelId, Request $request, $withQuery = null): Model
    {
        if ($withQuery && is_callable($withQuery)) {
            $withQuery($query);
        }
        $query->where($this->primaryKey, $modelId);
        $this->runIncludes($query, $request->get('include', null));

        return $query->firstOrFail();
    }
}
