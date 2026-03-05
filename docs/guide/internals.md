# Internals

This package provides a consistent set of [Composer](https://getcomposer.org/) scripts for local validation.

Tool references:

- [PHPCodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) for code style checks.
- [PHPStan](https://phpstan.org/) for static analysis.
- [PHPUnit](https://phpunit.de/) for unit tests.

## Code style checks (PHPCodeSniffer)

Run code style checks.

```bash
composer cs
```

Fix code style issues.

```bash
composer cs-fix
```

## Static analysis (PHPStan)

Run static analysis.

```bash
composer static
```

## Unit tests (PHPUnit)

Run the full test suite.

```bash
composer tests
```

## Passing extra arguments

Composer scripts support forwarding additional arguments using `--`.

Run PHPStan with a different memory limit.

```bash
composer static -- --memory-limit=512M
```
