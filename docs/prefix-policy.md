# Prefix policy

Prefix handling is part of an identifier definition and must never be inferred globally from the jurisdiction code.

## Policies

`PrefixDefinition` supports four explicit policies:

- `NONE` — the specification defines no prefix semantics;
- `REQUIRED` — the literal prefix is part of the canonical normalized value;
- `OPTIONAL` — the literal may be accepted by a validator according to its lenient-format rules, but is not part of the canonical value;
- `FORBIDDEN` — the literal identifies input that must be rejected for this identifier type and is not part of the canonical value.

Only `REQUIRED` prefixes are included in canonical lengths. Canonical lengths always describe the total normalized canonical value.

`CanonicalFormat` remains descriptive metadata. It does not strip, add, or match prefixes. Executable behavior belongs to the identifier-specific validator.

## Polish examples

### `PL:NIP`

```text
prefix policy: FORBIDDEN
literal prefix: PL
canonical character set: DIGITS
canonical length: 10
canonical value: ten digits
```

The country prefix is not part of a domestic NIP definition. A future `PL:NIP` validator must not silently transform `PL5260250995` into `5260250995`.

### `PL:VAT_EU`

```text
prefix policy: REQUIRED
literal prefix: PL
canonical character set: ASCII_ALPHANUMERIC
canonical length: 12
canonical value: PL followed by ten digits
```

`PL:NIP` and `PL:VAT_EU` are separate identifier definitions even when the numeric body uses related checksum rules.

## Compatibility

Passing a literal string as the fourth `CanonicalFormat` constructor argument is retained temporarily and maps to `PrefixDefinition::required()`. New definitions should use an explicit `PrefixDefinition`.
