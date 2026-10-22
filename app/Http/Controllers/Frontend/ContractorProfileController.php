<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContractorDocument;
use App\Models\ContractorProfile;
use App\Models\ContractorTrade;
use App\Services\Contractors\ContractorDocumentStore;
use App\Services\Contractors\ContractorMail;
use App\Support\ContractorCompliance;
use App\Support\PostcodeGeo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractorProfileController extends Controller
{
    public function __construct(
        private readonly ContractorDocumentStore $store,
        private readonly ContractorMail $mail,
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->hasRole('Contractor'), 403);

            return $next($request);
        });
    }

    public function edit(Request $request)
    {
        $profile = $this->profile($request);
        $profile->load(['documents', 'tradeApprovals']);

        if ($profile->approval_status === ContractorProfile::REMOVED) {
            abort(403, 'This contractor account has been removed.');
        }

        if ($profile->approval_status === ContractorProfile::SUBMITTED) {
            return redirect()->route('contractor.jobs.index');
        }

        $needsBasics = ContractorCompliance::basicsGaps($profile) !== [];
        if ($request->query('step') === 'business' || ($needsBasics && $this->editable($profile))) {
            return view('frontend.contractor.onboarding.business', [
                'profile' => $profile,
                'trades' => ContractorCompliance::trades(),
                'radii' => [5, 10, 15, 25, 50],
            ]);
        }

        return view('frontend.contractor.onboarding.documents', [
            'profile' => $profile,
            'required' => ContractorCompliance::requiredDocuments($profile),
            'optional' => ContractorCompliance::optionalDocuments($profile),
            'gaps' => ContractorCompliance::submissionGaps($profile),
            'store' => $this->store,
        ]);
    }

    public function updateBusiness(Request $request)
    {
        $profile = $this->profile($request);
        $data = $request->validate([
            'trading_type' => ['required', Rule::in(['sole_trader', 'limited_company'])],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'company_number' => ['nullable', 'string', 'max:16'],
            'trades' => ['required', 'array', 'min:1'],
            'trades.*' => [Rule::in(array_keys(ContractorCompliance::trades()))],
            'postcode' => ['required', 'string', 'max:16', 'regex:/^[A-Za-z]{1,2}\d[A-Za-z\d]?\s*\d[A-Za-z]{2}$/'],
            'coverage_radius_miles' => ['required', 'integer', Rule::in([5, 10, 15, 25, 50])],
            'vat_registered' => ['required', 'boolean'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:32'],
            'employs_staff' => ['nullable', 'boolean'],
        ], [
            'postcode.regex' => 'Enter a full UK postcode, for example M1 1AA.',
        ]);

        $limited = $data['trading_type'] === 'limited_company';
        $vat = (bool) $data['vat_registered'];
        $companyNumber = $limited ? strtoupper(preg_replace('/\s+/', '', (string) ($data['company_number'] ?? ''))) : null;

        if ($companyNumber) {
            $duplicate = ContractorProfile::query()
                ->where('company_number', $companyNumber)
                ->where('id', '!=', $profile->id)
                ->whereNotIn('approval_status', [ContractorProfile::REMOVED])
                ->exists();
            if ($duplicate) {
                return back()->withInput()->withErrors([
                    'company_number' => 'A contractor account already uses this company number. Log in to that account instead.',
                ]);
            }
        }

        $postcode = PostcodeGeo::normalise($data['postcode']);
        $location = $postcode !== $profile->postcode || $profile->latitude === null ? PostcodeGeo::locate($postcode) : null;
        $tradesChanged = collect($profile->trades ?? [])->sort()->values()->all()
            !== collect($data['trades'])->sort()->values()->all();
        $returnToReview = $profile->approval_status === ContractorProfile::APPROVED && (
            $tradesChanged
            || $postcode !== (string) $profile->postcode
            || (int) $data['coverage_radius_miles'] !== (int) $profile->coverage_radius_miles
            || $companyNumber !== ($profile->company_number ?: null)
        );

        $profile->fill([
            'trading_type' => $data['trading_type'],
            'trading_name' => $data['trading_name'] ?? null,
            'company_number' => $companyNumber,
            'postcode' => $postcode,
            'coverage_radius_miles' => (int) $data['coverage_radius_miles'],
            'vat_registered' => $vat,
            'vat_number' => $vat ? strtoupper(str_replace(' ', '', (string) ($data['vat_number'] ?? ''))) : null,
            'employs_staff' => $request->boolean('employs_staff'),
            'phone' => $request->exists('phone') ? ($data['phone'] ?: null) : $profile->phone,
        ]);
        if ($location) {
            [$profile->latitude, $profile->longitude] = $location;
        }
        $profile->save();
        ContractorCompliance::syncTrades($profile, $data['trades']);
        if ($returnToReview) {
            $profile->forceFill([
                'approval_status' => ContractorProfile::SUBMITTED,
                'submitted_at' => now(),
            ])->save();
            $profile->log('changes_submitted', $request->user(), null, 'Trade, postcode, radius, or company number changed.');
        }

        $gaps = ContractorCompliance::basicsGaps($profile->fresh());
        if ($gaps !== []) {
            return back()->withInput()->with('error', $gaps[0]);
        }

        return redirect()->route('contractor.profile.edit');
    }

    public function bank(Request $request)
    {
        $profile = $this->profile($request);
        abort_if($profile->approval_status === ContractorProfile::REMOVED, 403, 'This contractor account has been removed.');

        return view('frontend.contractor.bank', ['profile' => $profile]);
    }

    public function updateBank(Request $request)
    {
        $profile = $this->profile($request);
        abort_if($profile->approval_status === ContractorProfile::REMOVED, 403, 'This contractor account has been removed.');

        $data = $request->validate([
            'bank_account_name' => ['required', 'string', 'max:191'],
            'bank_sort_code' => ['required', 'string', 'max:12'],
            'bank_account_number' => ['required', 'string', 'max:12'],
            'bank_name' => ['nullable', 'string', 'max:191'],
        ]);

        $sortCode = preg_replace('/\D/', '', $data['bank_sort_code']) ?? '';
        $accountNumber = preg_replace('/\D/', '', $data['bank_account_number']) ?? '';
        $errors = [];
        if (strlen($sortCode) !== 6) {
            $errors['bank_sort_code'] = 'Enter a 6-digit sort code, for example 11-22-33.';
        }
        if (strlen($accountNumber) !== 8) {
            $errors['bank_account_number'] = 'Enter an 8-digit account number.';
        }
        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        $profile->forceFill([
            'bank_account_name' => trim($data['bank_account_name']),
            'bank_sort_code' => $sortCode,
            'bank_account_number' => $accountNumber,
            'bank_name' => filled($data['bank_name'] ?? null) ? trim($data['bank_name']) : null,
        ])->save();

        return redirect()->route('contractor.profile.bank')->with('success', 'Payment details saved.');
    }

    public function updateDocuments(Request $request)
    {
        $profile = $this->profile($request);
        $profile->load(['documents', 'tradeApprovals']);
        $allowed = array_merge(ContractorCompliance::requiredDocuments($profile), ContractorCompliance::optionalDocuments($profile));

        $rules = [
            'utr' => ['nullable', 'regex:/^\d{10}$/'],
            'submit' => ['nullable', 'boolean'],
        ];
        foreach ($allowed as $type) {
            $rules['docs.'.$type.'.file'] = ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,heic,webp,pdf'];
            $rules['docs.'.$type.'.reference'] = ['nullable', 'string', 'max:64'];
            $rules['docs.'.$type.'.issuer'] = ['nullable', 'string', 'max:64'];
            $rules['docs.'.$type.'.cover_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['docs.'.$type.'.expires_at'] = ['nullable', 'date', 'after:today'];
        }
        $request->validate($rules, [
            'utr.regex' => 'A UTR is 10 digits.',
            'docs.*.expires_at.after' => 'That document has already expired. Upload the current one.',
            'docs.*.file.mimes' => 'Upload a photo or a PDF.',
            'docs.*.file.max' => 'Files must be under 10 MB.',
        ]);

        foreach ($allowed as $type) {
            $input = (array) $request->input('docs.'.$type, []);
            $file = $request->file('docs.'.$type.'.file');
            $attributes = array_filter([
                'reference' => isset($input['reference']) ? strtoupper(trim((string) $input['reference'])) : null,
                'issuer' => $input['issuer'] ?? null,
                'cover_amount' => $input['cover_amount'] ?? null,
                'expires_at' => $input['expires_at'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
            if (! $file && $attributes === []) {
                continue;
            }
            $this->store->save($profile, $type, $file, $attributes);
        }

        if ($request->filled('utr')) {
            $profile->forceFill(['utr' => $request->string('utr')->toString()])->save();
        }

        $profile->refresh()->load(['documents', 'tradeApprovals']);
        if (! $request->boolean('submit')) {
            return redirect()->route('contractor.profile.edit')->with('success', 'Saved. You can finish later.');
        }

        return $this->submit($profile);
    }

    public function reapply(Request $request)
    {
        $profile = $this->profile($request);
        abort_unless($profile->approval_status === ContractorProfile::REJECTED, 403);
        $profile->forceFill(['approval_status' => ContractorProfile::DRAFT, 'draft_reminder_stage' => 0])->save();
        $profile->tradeApprovals()->where('status', ContractorTrade::REJECTED)->update(['status' => ContractorTrade::PENDING, 'reason' => null]);
        $profile->log('reapplied', $request->user());

        return redirect()->route('contractor.profile.edit')->with('success', 'Fix the items marked below, then send for review again.');
    }

    private function submit(ContractorProfile $profile)
    {
        $gaps = ContractorCompliance::submissionGaps($profile);
        if ($gaps !== []) {
            return redirect()->route('contractor.profile.edit')->with('error', $gaps[0]);
        }

        if ($profile->approval_status === ContractorProfile::APPROVED) {
            $pending = $profile->documents->where('status', ContractorDocument::PENDING)->count()
                + $profile->tradeApprovals->where('status', ContractorTrade::PENDING)->count();
            if ($pending > 0) {
                $profile->log('changes_submitted', $profile->user);
                $this->alertAdmins($profile, 'Contractor update waiting for review');
            }

            return redirect()->route('contractor.jobs.index')->with('success', $pending ? 'Sent for review. Your approved trades keep working meanwhile.' : 'Saved.');
        }

        if (! $this->editable($profile)) {
            return redirect()->route(
                $profile->approval_status === ContractorProfile::SUBMITTED
                    ? 'contractor.jobs.index'
                    : 'contractor.profile.edit'
            );
        }

        $profile->forceFill([
            'approval_status' => ContractorProfile::SUBMITTED,
            'submitted_at' => now(),
        ])->save();
        $profile->tradeApprovals()->where('status', ContractorTrade::MORE_INFO)->update(['status' => ContractorTrade::PENDING]);
        $profile->log('submitted', $profile->user);
        $this->alertAdmins($profile, 'New contractor waiting for review');
        $this->mail->toUser(
            $profile->user,
            'We have your documents',
            '<p>Thanks. Your account is pending review. We aim to check documents within '.e((string) \App\Support\ContractorSettings::get('review_sla_working_days')).' working days and will email you either way.</p>'
        );

        return redirect()->route('contractor.jobs.index')->with('success', 'Sent for review. We’ll email you when you can take jobs.');
    }

    private function alertAdmins(ContractorProfile $profile, string $subject): void
    {
        $this->mail->toAdmins(
            $subject,
            '<p>'.e($profile->displayName()).' ('.e(implode(', ', array_map(fn ($t) => ContractorCompliance::trades()[$t] ?? $t, $profile->trades ?? []))).') is waiting for review.</p>'
                .'<p><a href="'.e(route('admin.registrations.contractors.show', $profile)).'">Open the review</a></p>'
        );
    }

    private function editable(ContractorProfile $profile): bool
    {
        return in_array($profile->approval_status, [ContractorProfile::DRAFT, ContractorProfile::CHANGES], true);
    }

    private function profile(Request $request): ContractorProfile
    {
        return ContractorProfile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'approval_status' => ContractorProfile::DRAFT,
                'legal_name' => $request->user()->name,
                'phone' => $request->user()->phone,
            ]
        );
    }
}
