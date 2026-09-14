# Import local DB to cPanel so it matches local

## File to use
**Use this on cPanel:** `docs/local-db-for-cpanel-import-nosuper.sql`

That file is the local dump with:
- AUTO_INCREMENT kept on `id` columns
- Current columns including `properties.epc_required`
- No `CREATE DATABASE` / `USE` (you select the cPanel DB)
- **No `DEFINER=root@localhost`** (avoids MySQL error #1227 SUPER privilege on shared hosting)

Do **not** use the older `local-db-for-cpanel-import.sql` if you hit Access denied / SUPER — that one still has view DEFINER clauses.

Also kept for reference: `docs/local-db-for-staging-YYYYMMDD-HHMM.sql` (includes CREATE DATABASE for local name).

## Before import (important)
1. In cPanel → MySQL, note your real DB name (e.g. `castopcl_resisquare_laravel`).
2. Backup the current staging DB first (Export).
3. Import into an **empty** database, or drop all tables first.
   - phpMyAdmin → select DB → Check all → Drop → Yes
   - Do **not** mix old broken tables with this dump.

## Import (phpMyAdmin)
1. Select the **staging** database (not “create new” with the local name).
2. If a previous import failed halfway: **Check all → Drop** tables, then import again (partial imports leave a mess).
3. Import → Choose file → **`local-db-for-cpanel-import-nosuper.sql`**
4. Format: SQL
5. Go
6. If the file is too large, raise `upload_max_filesize` / use cPanel Terminal or SSH:
   `mysql -u USER -p DB_NAME < local-db-for-cpanel-import-nosuper.sql`

If you still see `#1227 SUPER`, the file still has a DEFINER — use only the `-nosuper` file.

## After import — verify
Run in phpMyAdmin SQL tab:

```sql
SHOW COLUMNS FROM registrations LIKE 'id';
SHOW COLUMNS FROM properties LIKE 'epc_required';
SHOW COLUMNS FROM users LIKE 'email';
```

Expect:
- `registrations.id` → Extra = `auto_increment`
- `epc_required` → present
- then retry register / add property

## .env on server
Point `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` at the cPanel DB you imported into.
Do not change app code for this step.

## What this fixes vs what it does not
**Fixed by this dump:** missing columns, lost AUTO_INCREMENT, schema drift from a bad/old phpMyAdmin export.

**Not fixed by dump alone:** MySQL on cPanel is often **stricter** than local MariaDB.
- Local allows inserts without `email` (empty string).
- Staging may still error: `Field 'email' doesn't have a default value` on quick-add contact.
- Staging may still 500 on notifications `DISTINCT` + `ORDER BY` under `ONLY_FULL_GROUP_BY`.

Those need a small app fix (or matching `sql_mode`). Schema will still match local after this import.

## Re-export later (from your PC)
```bat
C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 --databases resisquare_laravel_webdeveloper --result-file=docs\local-db-for-staging.sql
```
Then strip `CREATE DATABASE` / `USE` before cPanel import, or edit the DB name to match cPanel.
