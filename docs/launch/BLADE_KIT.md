# Blade kit (Launch Step 35)

One visual language for landlord workspace and tenant portal IN routes.

## Classes

| Class | Role |
|---|---|
| `lw-hero` | Page header band |
| `lw-card` | Content panel |
| `lw-table` | Quiet data table |
| `lw-btn` + `lw-btn-primary` / `secondary` / `danger` / `ghost` | Buttons |
| `lw-empty` | Designed empty state |
| `lw-pill` / `lw-status` + `-ok` `-warn` `-bad` `-idle` | Status chips |
| `lw-chip-filters` | Page-level status filters (not sidebar) |

## Components

```blade
<x-lw.hero title="Finance" subtitle="Rent invoices for this account." />
<x-lw.card>…</x-lw.card>
<x-lw.btn variant="primary" href="{{ route('admin.finance.create') }}">New invoice</x-lw.btn>
<x-lw.pill tone="ok">Paid</x-lw.pill>
<x-lw.status tone="warn">Pending</x-lw.status>
<x-lw.empty title="No invoices yet">Issue rent from a tenancy.</x-lw.empty>
```

## Bootstrap ban

On `body.landlord-workspace` and `body.tenant-portal-shell`, `btn-primary` / `btn-warning` / `btn-danger` and matching badges/alerts are remapped to the kit palette. Prefer `lw-btn-*` in new markup.
