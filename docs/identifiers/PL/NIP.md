# PL:NIP identifier specification

## Identity

```text
key: PL:NIP
category: TAX
rule set: PL_NIP
rule-set version: 1.0.0
reviewed at: 2026-07-11
validation level: FORMAT_AND_CHECKSUM
```

## Canonical format

The canonical value contains exactly ten ASCII digits.

```text
5260250995
```

The `PL` country prefix is forbidden for `PL:NIP`. A prefixed value belongs to the separate `PL:VAT_EU` identifier type.

## Normalization

Strict mode accepts only the canonical ten-digit value.

Lenient mode may remove ASCII spaces and hyphens. It does not remove or reinterpret a country prefix.

## Checksum

The first nine digits are multiplied by the weights:

```text
6, 5, 7, 2, 3, 4, 5, 6, 7
```

The weighted sum is reduced modulo 11. A remainder of 10 is invalid. Every other remainder must equal the tenth digit.
