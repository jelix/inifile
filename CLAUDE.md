# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`jelix/inifile` is a small standalone PHP library (part of the Jelix framework ecosystem) for
reading and modifying INI files while **preserving comments, whitespace, and formatting**. It is
not a generic INI reader — for pure read-only parsing, `parse_ini_file()` or `Util::read()` is
preferred; this library exists specifically to support round-trip edit/save of INI files.

PHP compat: `>=7.4` (per `composer.json`) — avoid using syntax newer than that in `lib/`.

## Common commands

Install dependencies:
```bash
composer install
```

Run the full test suite (PHPUnit, config at `tests/phpunit.xml`):
```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

Run a single test file or test method:
```bash
vendor/bin/phpunit -c tests/phpunit.xml tests/IniModifierSetTest.php
vendor/bin/phpunit -c tests/phpunit.xml --filter testMethodName tests/IniModifierSetTest.php
```

Run tests in Docker (useful for testing against a specific PHP version, see `tests/Dockerfile`):
```bash
export PHP_VERSION=8.1   # 7.4, 8.0, 8.1, 8.4, ...
./tests/launchtests build
./tests/launchtests tests
```

PHP-CS-Fixer config exists at `.php_cs.dist` (rules: `@PSR1`, `@PSR2`, targets `lib/`).

## Architecture

### Two families of classes

- **Read-only parsing**: `IniReader` implements `IniReaderInterface` (get values/sections only).
- **Read+modify**: `IniModifier extends IniReader` and implements `IniModifierInterface`
  (adds `setValue`/`setValues`/`removeValue`/`removeSection`/`save`/rename/merge/import).

`IniModifierReadOnly` wraps any `IniReaderInterface`/`IniModifierInterface` object and exposes
only the read-only interface — used to hand out a non-mutable view of a modifiable ini object.

`IniModifierArray` and `MultiIniModifier` compose multiple ini objects (or filenames, which they
wrap in `IniModifier` automatically) and implement `IniModifierInterface` themselves:
- `MultiIniModifier`: exactly two files — a master file and an overrider file. Reads check the
  overrider first, then fall back to the master; `setValue`/writes go to the overrider only
  (some rename/merge operations affect both files — see `MultiIniModifier.php` for per-method
  scoping).
- `IniModifierArray`: an ordered list of ini objects, last one has priority; implements
  `IteratorAggregate`/`ArrayAccess`/`Countable` over the underlying modifiers.

`Util` is a separate, stateless helper class (not part of the Reader/Modifier hierarchy) built on
top of native `parse_ini_file()`/`INI_SCANNER_TYPED` for fast reads, config-merging
(`mergeIniObjectContents`), and array-to-ini-string serialization. Prefer it when you don't need
comment/whitespace-preserving round-trip editing.

### How `IniReader`/`IniModifier` preserve formatting

The core trick (`IniReader::parse()`) is a line-by-line tokenizer that turns the raw file into a
`content` array keyed by section name (section `0` = the unnamed/global section at the top of the
file). Each line becomes a token tuple:
- `TK_WS` — whitespace/blank line, kept verbatim so blank lines round-trip
- `TK_COMMENT` — a comment line (`;...` always, `#...` too if `FORMAT_COMMENT_HASH` is passed)
- `TK_SECTION` — a `[section]` header line
- `TK_VALUE` — `name=value`
- `TK_ARR_VALUE` — `name[]=value` or `name[key]=value` (array/associative array items), tuple
  includes the resolved key

`IniModifier` mutates this token array in place rather than the parsed values directly: removing
a value replaces its token with `array(TK_WS, '--')` (a sentinel skipped at generation time) so
surrounding comments/whitespace stay put; `generateIni()` walks the token array back into text.
This is why most mutation methods manipulate `$this->content[$section]` token arrays directly
instead of going through `getValue`/simple key-value maps — any change here needs to preserve that
token-array shape and the `TK_WS '--'` deletion-sentinel convention.

`removeValue`/`removeSection` also try to clean up the comment/whitespace lines immediately
preceding the removed item (`$removePreviousComment` param) — see the `previousComment` tracking
logic in `IniModifier::removeValue()`.

Array values (`foo[]=` / `foo[key]=`) are the main source of complexity throughout `IniModifier`
and `IniReader` — most methods have a distinct branch for `TK_ARR_VALUE` vs `TK_VALUE` handling,
including numeric-vs-string key handling on output (`foo[]=` for numeric keys vs `foo[key]=` for
associative ones).

### Value typing

`IniReader::convertValue()` mimics `parse_ini_file`'s scalar coercion (int/float/bool for
`true`/`on`/`yes`/`false`/`off`/`no`/`none`, else string) when *reading* values back out.
`IniModifier::getIniValue()` is the inverse for *writing*: numeric/bareword values are written
unquoted, booleans become `on`/`off`, everything else gets wrapped in double quotes (values
containing a literal `"` are not supported — see the "totally bugged" caveat in `IniReader`'s
class docblock about why this library doesn't try to match `parse_ini_file`'s quote handling
exactly).

### Save flags

`IniModifierInterface::FORMAT_NO_QUOTES` and `FORMAT_SPACE_AROUND_EQUAL` are bitmask flags passed
to `save($chmod, $format)`/`saveAs($filename, $format)`, not constructor options.
`IniReaderInterface::FORMAT_COMMENT_HASH` is a constructor-time flag (parsing behavior) shared by
both reader and modifier constructors.


