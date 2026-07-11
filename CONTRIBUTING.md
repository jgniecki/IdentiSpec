# Contributing to IdentiSpec Core

IdentiSpec Core accepts changes that improve the accuracy, safety, and maintainability of offline identifier validation. Supporting more jurisdictions is secondary to trustworthy rules and tests.

## Language and compatibility

- Write public documentation, code identifiers, comments, issues, and change descriptions in English.
- Target PHP 8.2 and later unless an accepted ADR changes the requirement.
- Treat the public contracts in `docs/architecture.md` as compatibility commitments.
- Do not add framework dependencies or configuration loaders to core.

## Before implementation

A new identifier starts with a completed [identifier specification](docs/identifier-specification-template.md). The proposal must establish:

- a unique `JURISDICTION:TYPE` key;
- official or independently corroborated rule sources;
- canonical and lenient representations;
- executable rules and historical applicability;
- capability claims and validation level;
- positive, negative, boundary, privacy, and mutation scenarios;
- a rule-set identifier and version.

Do not copy an algorithm from another package without independent confirmation. Cross-library comparison can reveal discrepancies but cannot resolve them without authoritative evidence.

## Architecture rules

- Implement a validator for one identifier type, not one jurisdiction.
- Keep `IdentifierDefinition` descriptive; executable algorithms belong to PHP validators or explicitly composed PHP rules.
- Do not add an algorithm name that core resolves dynamically.
- Prefer dedicated code for complex or conditional behavior.
- Extract a shared primitive only after real validators demonstrate compatible semantics.
- Keep `PL:NIP` and `PL:VAT_EU` separate.
- Never broaden `VALID` to mean verified, registered, issued, or active.

## Input and privacy rules

- `STRICT` is the default mode.
- `LENIENT` may apply only transformations explicitly approved by the identifier specification.
- Never strip unknown characters generically.
- Never place raw identifiers in exceptions, logs, fixtures, snapshots, or diagnostic messages.
- Treat normalized and derived values as sensitive.
- Sanitize nested issue, warning, and transformation context in safe output.
- Do not add network calls or telemetry to core.

## Rule changes

A behavior-changing correction or extension must include:

1. the source or evidence motivating the change;
2. an updated identifier specification and review date;
3. a rule-set version change;
4. positive and negative regression cases;
5. an explanation of changed outcomes;
6. release notes once release management exists.

Package version and rule-set version are separate. Do not rewrite history to hide a previous interpretation.

## Architecture decisions

Create an ADR for a change that alters public semantics, extension boundaries, privacy guarantees, registry identity, or the meaning of a result. Accepted ADRs are immutable; supersede them with a new record.

## Development environment

Build the reproducible PHP 8.2 development image and install the locked dependencies:

```bash
docker build --tag identispec-dev --file docker/dev/Dockerfile .
docker run --rm --volume "$PWD:/app" --workdir /app identispec-dev composer install
```

Run all Stage 1 quality gates with:

```bash
docker run --rm --volume "$PWD:/app" --workdir /app identispec-dev composer check
```

The same Composer commands can be run directly when PHP 8.2+ and Composer 2 are available on the host.

## Test expectations

Every validator is required to pass common contract tests and its identifier-specific corpus. Tests must cover arbitrary strings without uncontrolled exceptions, deterministic output, normalization boundaries, declared capabilities, unsupported keys, safe serialization, and absence of network access.

PHPUnit runs on PHP 8.2 through 8.5 in CI. PHPStan runs at level `max` without a baseline. New code must keep both gates green.

## Documentation maintenance

- Update the owning normative document with the behavior it governs.
- Add new documentation pages to `docs/README.md`.
- Clearly label experiments and historical drafts as non-normative.
- Resolve conflicts in favor of accepted ADRs and the current architecture contract.
