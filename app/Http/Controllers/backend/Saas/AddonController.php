<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\Addon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddonController extends BaseSaasController
{
    private const ADDON_TYPES = [
        'property',
        'branch',
        'staff',
        'property_manager',
    ];

    public function index()
    {
        $addons = Addon::query()
            ->withCount('subscriptionAddons')
            ->orderBy('addon_type')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('backend.saas.addons.index', compact('addons'));
    }

    public function create()
    {
        $addon = new Addon([
            'currency' => 'GBP',
            'grant_quantity' => 1,
            'is_stackable' => true,
            'is_active' => true,
        ]);

        $addonTypes = self::ADDON_TYPES;

        return view('backend.saas.addons.create', compact('addon', 'addonTypes'));
    }

    public function store(Request $request)
    {
        $this->normaliseCode($request);

        $data = $this->validatedData($request);
        $data['monthly_price_minor'] = $this->priceToMinor($data['monthly_price']);
        $data['annual_price_minor'] = $this->priceToMinor($data['annual_price']);
        $data['is_stackable'] = $request->boolean('is_stackable');
        $data['is_active'] = $request->boolean('is_active');
        unset($data['monthly_price'], $data['annual_price']);

        Addon::create($data);

        flash('Addon created successfully.')->success();

        return redirect()->route('backend.saas.addons.index');
    }

    public function show(Addon $addon)
    {
        return redirect()->route('backend.saas.addons.edit', $addon);
    }

    public function edit(Addon $addon)
    {
        $addonTypes = self::ADDON_TYPES;

        return view('backend.saas.addons.edit', compact('addon', 'addonTypes'));
    }

    public function update(Request $request, Addon $addon)
    {
        $this->normaliseCode($request);

        $data = $this->validatedData($request, $addon);
        $data['monthly_price_minor'] = $this->priceToMinor($data['monthly_price']);
        $data['annual_price_minor'] = $this->priceToMinor($data['annual_price']);
        $data['is_stackable'] = $request->boolean('is_stackable');
        $data['is_active'] = $request->boolean('is_active');
        unset($data['monthly_price'], $data['annual_price']);

        $addon->update($data);

        flash('Addon updated successfully.')->success();

        return redirect()->route('backend.saas.addons.index');
    }

    public function destroy(Addon $addon)
    {
        if ($addon->subscriptionAddons()->exists()) {
            flash('This addon is used by subscriptions. Deactivate it instead of deleting it.')->error();

            return back();
        }

        $addon->delete();

        flash('Addon deleted successfully.')->success();

        return redirect()->route('backend.saas.addons.index');
    }

    private function validatedData(Request $request, ?Addon $addon = null): array
    {
        $uniqueCode = Rule::unique('addons', 'code');
        if ($addon) {
            $uniqueCode = $uniqueCode->ignore($addon->id);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', $uniqueCode],
            'name' => ['required', 'string', 'max:255'],
            'addon_type' => ['required', Rule::in(self::ADDON_TYPES)],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stripe_monthly_price_id' => ['nullable', 'string', 'max:255'],
            'stripe_annual_price_id' => ['nullable', 'string', 'max:255'],
            'grant_quantity' => ['required', 'integer', 'min:1'],
        ]);
    }
}
