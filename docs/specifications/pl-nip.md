# PL:NIP identifier specification

## Status

- Proposal status: `IMPLEMENTED`
- Owner: IdentiSpec Core maintainers
- Last reviewed: `2026-07-11`

## Identity and semantics

- Identifier key: `PL:NIP`
- Jurisdiction: Poland (`PL`)
- Type: Polish Tax Identification Number (`NIP`)
- Category: `TAX`
- Canonical value: ten ASCII digits
- Prefix policy: `FORBIDDEN`, literal `PL`

`VALID` means that the value has the canonical NIP format and passes the implemented offline checksum rule. It does not establish that the number was issued, exists in KAS, belongs to an entity, remains active, or represents a VAT-registered taxpayer.

`PL:NIP` is distinct from `PL:VAT_EU`. The domestic canonical NIP does not contain a country prefix.

## Sources

1. [Polish Ministry of Finance — Check NIP status](https://sprawdznip.podatki.gov.pl/) states that NIP contains ten digits and distinguishes status verification from local format validation.
2. [European Commission notice 2016/C 481/08](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:52016XC1223(02)) records the Polish TIN format as ten numerals.
3. [Algorytm.org NIP implementation](https://www.algorytm.org/numery-identyfikacyjne/nip/nip-d.html) documents the independently corroborated checksum weights and modulo operation. This is a secondary algorithm source, not evidence that a number was issued.

The reviewed official sources establish the canonical format but do not provide a machine-oriented checksum specification. The checksum interpretation is therefore explicitly versioned and backed by regression tests.

## Normalization

`STRICT` accepts only ten consecutive ASCII digits.

`LENIENT` additionally accepts exactly one of these presentation layouts:

```text
DDD-DDD-DD-DD
DDD DDD DD DD
```

Separators must be consistent and in the documented positions. Each removed separator is recorded as a transformation. Mixed, leading, trailing, repeated, or differently positioned separators are invalid. The `PL` prefix is never removed, including in `LENIENT`.

## Validation rules

Rules execute in this order:

1. reject the forbidden uppercase `PL` prefix;
2. normalize ASCII digits and documented separators;
3. validate the lenient presentation layout when transformations occurred;
4. require ten canonical digits;
5. multiply the first nine digits by `6, 5, 7, 2, 3, 4, 5, 6, 7`;
6. calculate the sum modulo `11`;
7. reject remainder `10`; otherwise require the remainder to equal the tenth digit.

The validator composes `NumericNormalizer` and `WeightedModulo`. The definition remains descriptive and does not select either component by name.

## Capabilities and rule set

- Validation level: `FORMAT_AND_CHECKSUM`
- Capabilities: `CHARACTERS`, `LENGTH`, `PREFIX`, `STRUCTURE`, `CHECKSUM`
- Rule-set identifier: `PL_NIP`
- Rule-set version: `1.0.0`
- Historical variants: none documented by the reviewed sources

The test corpus uses deterministically constructed synthetic values. It covers canonical and lenient forms, forbidden prefixes, malformed layouts, length and character failures, checksum mutations, modulo-11 remainder `10`, facade routing, deterministic behavior, and safe serialization.

## Known limitations

- No KAS, VIES, or other registry lookup is performed.
- Taxpayer identity, issuance, activity, and VAT status are not evaluated.
- Historical office-prefix allocation is not interpreted.
- Lowercase or otherwise transformed country prefixes are not accepted as NIP input.
