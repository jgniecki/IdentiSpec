# PL:PESEL identifier specification

## Status

- Proposal status: `IMPLEMENTED`
- Owner: IdentiSpec Core maintainers
- Last reviewed: `2026-07-11`

## Identity and semantics

- Identifier key: `PL:PESEL`
- Jurisdiction: Poland (`PL`)
- Type: Universal Electronic System for Registration of the Population number (`PESEL`)
- Category: `PERSONAL_ID`
- Canonical value: eleven ASCII digits
- Prefix policy: `NONE`

`VALID` means that the value has the canonical PESEL format, contains a valid encoded Gregorian birth date in the supported statutory range, and passes the offline checksum rule. It does not establish that the number was issued, exists in the PESEL register, belongs to a person, remains active, or is not reserved.

PESEL is sensitive personal data. Validation does not expose the encoded birth date, gender marker, ordinal segment, or any other derived personal information.

## Sources

1. [Gov.pl — What is a PESEL number](https://www.gov.pl/web/gov/czym-jest-numer-pesel) documents the eleven-digit layout, encoded centuries, gender marker, checksum weights, and checksum calculation.
2. [Polish Population Registration Act — consolidated text](https://eli.gov.pl/api/acts/DU/2024/736/text.html) defines PESEL as an eleven-digit identifier containing the encoded birth date, ordinal number, gender marker, and check digit.

## Canonical representation and normalization

The canonical layout is:

```text
YYMMDDSSSSC
```

- `YYMMDD` contains the birth date with the century encoded in the month;
- `SSSS` is the ordinal segment whose final digit carries the statutory gender marker;
- `C` is the checksum digit.

Both `STRICT` and `LENIENT` accept only eleven consecutive ASCII digits. PESEL has no official separated presentation format, so no spaces, hyphens, Unicode digits, case conversion, or other presentation transformations are allowed.

## Embedded birth date

The encoded month maps to a century as follows:

| Encoded month | Century | Month offset |
|---|---:|---:|
| `81`–`92` | 1800–1899 | 80 |
| `01`–`12` | 1900–1999 | 0 |
| `21`–`32` | 2000–2099 | 20 |
| `41`–`52` | 2100–2199 | 40 |
| `61`–`72` | 2200–2299 | 60 |

After decoding the century and month, the complete date must be a valid Gregorian calendar date. Invalid month encodings, zero days, impossible month days, and invalid leap days produce `INVALID_EMBEDDED_VALUE` at byte position `0`.

## Checksum

The first ten digits are multiplied by:

```text
1, 3, 7, 9, 1, 3, 7, 9, 1, 3
```

The expected final digit is `(10 - (sum % 10)) % 10`. A mismatch produces `INVALID_CHECKSUM` at byte position `10`.

The algorithm remains dedicated executable PHP inside the PESEL validator. It is not selected through metadata or a declarative configuration.

## Capabilities and rule set

- Validation level: `FULL_OFFLINE_RULES`
- Capabilities: `CHARACTERS`, `LENGTH`, `STRUCTURE`, `CHECKSUM`, `EMBEDDED_SEMANTICS`
- Rule-set identifier: `PL_PESEL`
- Rule-set version: `1.0.0`
- Historical variants: none outside the statutory century encoding documented by the reviewed sources

The test corpus uses the example published by Gov.pl and deterministically constructed synthetic values. Synthetic values are not looked up in any registry and may not be treated as issued identifiers.

## Known limitations

- No PESEL-register lookup is performed.
- Issuance, ownership, identity, reservation status, and current administrative state are not evaluated.
- The gender marker is neither returned nor compared with caller-provided data.
- No birth date or other segment is exposed through diagnostics or safe serialization.
