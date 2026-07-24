<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Models\Company;
use App\Models\GlAccount;
use App\Models\GlJournal;
use App\Models\GlJournalLine;
use App\Models\User;
use Illuminate\Http\Request;

class GlJournalLineController extends BaseCrudController
{
    protected string $modelClass = GlJournalLine::class;
    protected string $viewPath = 'backend.accounting.shared';
    protected string $routeName = 'backend.accounting.gl_journal_lines';
    protected string $title = 'GL Journal Lines';
    protected array $columns = [
        ['key' => 'id', 'label' => '#'],
        ['key' => 'journal.display_name', 'label' => 'Journal'],
        ['key' => 'account.code', 'label' => 'Account Code'],
        ['key' => 'account.name', 'label' => 'Account Name'],
        ['key' => 'company.name', 'label' => 'Company'],
        ['key' => 'user.name', 'label' => 'User'],
        ['key' => 'debit', 'label' => 'Debit', 'type' => 'money'],
        ['key' => 'credit', 'label' => 'Credit', 'type' => 'money'],
    ];
    protected array $with = ['journal', 'account', 'company', 'user'];

    protected function fields(): array
    {
        return [
            ['name' => 'gl_journal_id', 'label' => 'Journal', 'type' => 'select', 'required' => true],
            ['name' => 'gl_account_id', 'label' => 'Account', 'type' => 'select', 'required' => true],
            ['name' => 'company_id', 'label' => 'Company', 'type' => 'select'],
            ['name' => 'user_id', 'label' => 'User', 'type' => 'select'],
            ['name' => 'debit', 'label' => 'Debit', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
            ['name' => 'credit', 'label' => 'Credit', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true],
        ];
    }

    protected function options(): array
    {
        $journalOptions = GlJournal::orderByDesc('id')->limit(200)->get()->mapWithKeys(function ($journal) {
            return [$journal->id => 'Journal #' . $journal->id . ' (' . $journal->date . ')'];
        });

        $accountOptions = GlAccount::orderBy('code')->get()->mapWithKeys(function ($account) {
            return [$account->id => $account->code . ' - ' . $account->name];
        });
        $companyOptions = Company::orderBy('name')->get()->pluck('name', 'id');
        $userOptions = User::orderBy('name')->get()->mapWithKeys(function ($user) {
            return [$user->id => $user->name . ($user->email ? ' (' . $user->email . ')' : '')];
        });

        return [
            'gl_journal_id' => $journalOptions,
            'gl_account_id' => $accountOptions,
            'company_id' => $companyOptions,
            'user_id' => $userOptions,
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'gl_journal_id' => ['required', 'exists:gl_journals,id'],
            'gl_account_id' => ['required', 'exists:gl_accounts,id'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'debit' => ['required', 'numeric', 'min:0'],
            'credit' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data = $this->preparePayload($request, $data);

        $journal = GlJournal::findOrFail($data['gl_journal_id']);
        $existingDebits = (float) $journal->lines()->sum('debit');
        $existingCredits = (float) $journal->lines()->sum('credit');
        $newDebits = $existingDebits + (float) $data['debit'];
        $newCredits = $existingCredits + (float) $data['credit'];

        if (abs($newDebits - $newCredits) > 0.01) {
            session()->flash('warning', 'Journal #' . $journal->id . ' is unbalanced. Total debits: ' . number_format($newDebits, 2) . ', Total credits: ' . number_format($newCredits, 2) . '. Please add offsetting entries.');
        }

        GlJournalLine::create($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $item = $this->query()->findOrFail($id);
        $data = $request->validate($this->rules($id));
        $data = $this->preparePayload($request, $data);

        $journal = GlJournal::findOrFail($data['gl_journal_id']);
        $existingDebits = (float) $journal->lines()->where('id', '!=', $id)->sum('debit') + (float) $data['debit'];
        $existingCredits = (float) $journal->lines()->where('id', '!=', $id)->sum('credit') + (float) $data['credit'];

        if (abs($existingDebits - $existingCredits) > 0.01) {
            session()->flash('warning', 'Journal #' . $journal->id . ' is unbalanced. Total debits: ' . number_format($existingDebits, 2) . ', Total credits: ' . number_format($existingCredits, 2) . '. Please review entries.');
        }

        $item->update($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', $this->title . ' updated successfully.');
    }
}
