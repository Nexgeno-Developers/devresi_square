# Empty / error / loading states (Launch Step 39)

## Empty lists

Use `<x-lw.empty title="…">` (optional `actions` slot) inside `lw-card` — never a blank `<tbody>`.

| Module | Empty copy |
|---|---|
| Finance | Nothing to invoice yet / No rent invoices yet |
| Tenancies | No tenancies yet |
| Documents | No documents yet |
| Repairs | No repairs yet |
| People | No portal users yet |
| Properties | No properties yet (PCC empty) |

## Errors

Branded pages: `resources/views/errors/{403,404,419,500}.blade.php` via `<x-errors.shell>`.

## Loading

`<x-lw.loading label="Loading…" />` for async panes (CSS `.lw-loading` in landlord-workspace.css).
