# Landlord MVP — Property Passport UX Reference

Reference product: [Property Passport UK](https://propertypassport.uk)

## Design principles (mandatory for landlord/tenant flows)

1. **Three steps max for onboarding** — `Step 01`, `Step 02`, `Step 03` with plain-language titles.
2. **Search-first address** — Postcode lookup is primary; manual entry is secondary.
3. **Property Passport review card** — Summary shows address, key stats, and a completeness percentage.
4. **Honest gaps** — Missing fields labelled "Not provided yet", never hidden.
5. **Calm UI** — White cards, green accent (`#1a5f4a`), generous spacing, no dense CRM tables in wizards.
6. **Mobile-first** — Stacked forms, 44px+ touch targets.
7. **Accessibility** — Visible labels, `aria-live` on address lookup status, keyboard-navigable controls.

## Phase 0 implementation

| Asset | Path |
|-------|------|
| Wizard layout | `resources/views/backend/properties/landlord-wizard/layout.blade.php` |
| Step views | `step-1.blade.php` … `step-3.blade.php` |
| Styles | `public/asset/backend/css/landlord-property-wizard.css` |
| Controller | `app/Http/Controllers/Backend/LandlordPropertyWizardController.php` |

## Route

- Landlord wizard: `/admin/properties/wizard`
- Legacy quick flow remains for non-landlord roles at `/admin/properties/quick-create`
