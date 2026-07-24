# UK property address lookup

Property forms use one server-side address provider selected through configuration. API
keys are never sent to the browser.

## Switching providers

Set one value in `.env`, then clear the Laravel configuration cache:

```dotenv
ADDRESS_LOOKUP_PROVIDER=postcodes_io
```

```bash
php artisan config:clear
```

Available values:

| Provider | API domain | Key required | Best use |
| --- | --- | --- | --- |
| `postcodes_io` | `https://api.postcodes.io` | No | Free postcode validation and locality data. The user enters the premise/street manually. |
| `nominatim` | `https://nominatim.openstreetmap.org` | No | User-triggered OpenStreetMap address search. Coverage is not a complete Royal Mail address list. |
| `getaddress` | `https://api.getAddress.io` | Yes | Full address suggestions and structured premise addresses. |
| `ideal_postcodes` | `https://api.ideal-postcodes.co.uk/v1` | Yes | Full Royal Mail postcode address lists and UPRNs. |

All API domains can be overridden without changing application code:

```dotenv
POSTCODES_IO_BASE_URL=https://api.postcodes.io
GETADDRESS_BASE_URL=https://api.getAddress.io
IDEAL_POSTCODES_BASE_URL=https://api.ideal-postcodes.co.uk/v1
NOMINATIM_BASE_URL=https://nominatim.openstreetmap.org
```

The application calls these provider endpoints:

| Provider | Search endpoint | Address resolution endpoint |
| --- | --- | --- |
| Postcodes.io | `/postcodes?query={postcode}` | `/postcodes/{postcode}` |
| getAddress | `/autocomplete/{term}` | `/get/{id}` |
| Ideal Postcodes | `/postcodes/{postcode}` | Included in the search response |
| Nominatim | `/search` | Included in the search response |

For the Royal Mail-style postcode finder, use `ideal_postcodes` or `getaddress`.
Once a complete postcode is entered, the form automatically opens a scrollable
premises list. `postcodes_io` cannot provide that list because it contains
postcode/locality data rather than delivery-point addresses.

For a keyed provider:

```dotenv
ADDRESS_LOOKUP_PROVIDER=getaddress
GETADDRESS_API_KEY=your-key
```

or:

```dotenv
ADDRESS_LOOKUP_PROVIDER=ideal_postcodes
IDEAL_POSTCODES_API_KEY=your-key
```

For public Nominatim, identify the application:

```dotenv
ADDRESS_LOOKUP_PROVIDER=nominatim
NOMINATIM_USER_AGENT="ResiSquare/1.0 (support@example.com)"
NOMINATIM_CONTACT_EMAIL=support@example.com
```

The Nominatim adapter caches repeated searches, accepts only explicit user searches, and
serializes requests to respect the public service's one-request-per-second limit. Review
the [Nominatim usage policy](https://operations.osmfoundation.org/policies/nominatim/)
before using it in production.

## Provider contract

All providers return the same fields:

```text
line_1, line_2, city, county, postcode, country, country_code, uprn
```

The browser therefore remains unchanged when the provider changes. New integrations only
need to implement `AddressLookupProvider` and be registered in `AddressLookupManager`.

Provider references:

- [Postcodes.io](https://postcodes.io/)
- [getAddress autocomplete API](https://documentation.getaddress.io/)
- [Ideal Postcodes postcode lookup](https://docs.ideal-postcodes.co.uk/docs/api/postcodes/)
