<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Models\GlJournal;
use App\Models\GlPeriodClose;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GlJournalController extends BaseCrudController
{
    protected string $modelClass = GlJournal::class;
    protected string $viewPath = 'backend.accounting.shared';
    protected string $routeName = 'backend.accounting.gl_journals';
    protected string $title = 'GL Journals';
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['key' => 'memo', 'label' => 'Memo'],
        ['key' => 'source_type', 'label' => 'Source Type'],
        ['key' => 'source_id', 'label' => 'Source ID'],
        ['key' => 'reversal_of_id', 'label' => 'Reversal Of'],
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
            ['name' => 'memo', 'label' => 'Memo', 'type' => 'text'],
            ['name' => 'source_type', 'label' => 'Source Type', 'type' => 'text'],
            ['name' => 'source_id', 'label' => 'Source ID', 'type' => 'number', 'min' => '1'],
            ['name' => 'reversal_of_id', 'label' => 'Reversal Of', 'type' => 'select'],
        ];
    }

    protected function options(): array
    {
        $journals = GlJournal::orderByDesc('id')->limit(100)->get()->pluck('id', 'id');

        return [
            'reversal_of_id' => $journals,
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'date' => ['required', 'date'],
            'memo' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'reversal_of_id' => ['nullable', 'integer', 'exists:gl_journals,id', Rule::notIn([$id])],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $period = Carbon::parse($data['date'])->format('Y-m');
        if (GlPeriodClose::isClosed($period)) {
            return redirect()->back()->withInput()
                ->with('error', "Period {$period} is closed. Cannot create journal entries in a closed period.");
        }

        $data = $this->preparePayload($request, $data);
        GlJournal::create($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $item = $this->query()->findOrFail($id);
        $data = $request->validate($this->rules($id));

        $period = Carbon::parse($data['date'])->format('Y-m');
        if (GlPeriodClose::isClosed($period)) {
            return redirect()->back()->withInput()
                ->with('error', "Period {$period} is closed. Cannot modify journal entries in a closed period.");
        }

        $data = $this->preparePayload($request, $data);
        $item->update($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' updated successfully.');
    }
}
