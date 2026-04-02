<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class BaseCrudController extends Controller
{
    protected string $modelClass;
    protected string $viewPath;
    protected string $routeName;
    protected string $title;
    protected array $columns = [];
    protected array $scope = [];
    protected array $with = [];
    protected array $booleanFields = [];
    protected array $fixedValues = [];
    protected array $defaults = [];

    abstract protected function rules(?int $id = null): array;
    abstract protected function fields(): array;

    protected function options(): array
    {
        return [];
    }

    protected function onStore(Request $request, array &$data): void
    {
        // Intended for child-class hooks.
    }

    protected function onUpdate(Request $request, array &$data, Model $item): void
    {
        // Intended for child-class hooks.
    }

    public function index()
    {
        $records = $this->query()->orderByDesc('id')->paginate(20);

        return view($this->viewPath . '.index', [
            'title' => $this->title,
            'records' => $records,
            'columns' => $this->columns,
            'routeName' => $this->routeName,
        ]);
    }

    public function create()
    {
        return view($this->viewPath . '.create', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'defaults' => $this->defaults,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data = $this->preparePayload($request, $data);
        $this->onStore($request, $data);

        $modelClass = $this->modelClass;
        $modelClass::create($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' created successfully.');
    }

    public function edit(int $id)
    {
        $item = $this->query()->findOrFail($id);

        return view($this->viewPath . '.edit', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'fields' => $this->fields(),
            'selectOptions' => $this->options(),
            'item' => $item,
            'defaults' => $this->defaults,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $item = $this->query()->findOrFail($id);
        $data = $request->validate($this->rules($id));
        $data = $this->preparePayload($request, $data);
        $this->onUpdate($request, $data, $item);
        $item->update($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' updated successfully.');
    }

    public function destroy(int $id)
    {
        $item = $this->query()->findOrFail($id);
        $item->delete();

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' deleted successfully.');
    }

    protected function query(): Builder
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::query();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        foreach ($this->scope as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    protected function preparePayload(Request $request, array $data): array
    {
        foreach ($this->booleanFields as $field) {
            $data[$field] = $request->boolean($field);
        }

        foreach ($this->fixedValues as $field => $value) {
            $data[$field] = $value;
        }

        return $data;
    }
}
