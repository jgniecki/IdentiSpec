# PL:VAT_EU identifier specification

## Identity

```text
key: PL:VAT_EU
category: TAX
rule set: PL_VAT_EU
rule-set version: 1.0.0
reviewed at: 2026-07-11
validation level: FORMAT_AND_CHECKSUM
```

## Canonical format

The canonical value contains the uppercase `PL` prefix followed by exactly ten ASCII digits.

```text
PL5260250995
```

The prefix is required and belongs to the canonical value. The ten-digit body uses the same checksum rule as `PL:NIP`.

## Normalization

Strict mode accepts only the uppercase canonical value without separators.

Lenient mode may normalize ASCII letter case and remove ASCII spaces and hyphens. The normalized result always contains uppercase `PL` followed by ten digits.

## Checksum

The checksum is calculated over the ten-digit body. The first nine body digits use the weights:

```text
6, 5, 7, 2, 3, 4, 5, 6, 7
```

The weighted sum is reduced modulo 11. A remainder of 10 is invalid. Every other remainder must equal the final body digit.
