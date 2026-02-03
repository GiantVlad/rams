---
name: laravel-pint
description: Code style fixer for PHP/Laravel. Use when asked to "fix code style", "format php", "run pint", or "check quality".
---

# Laravel Pint

## Overview

Laravel Pint is an opinionated PHP code style fixer for minimalists. This skill guides the execution of Pint to automatically fix coding style issues in PHP files.

## Usage

### 1. Verification

Before running, verify Pint is installed in the project:

```bash
ls vendor/bin/pint
# OR inside a subdirectory like 'api/'
ls api/vendor/bin/pint
```

If not found, ask the user if they want to install it via `composer require laravel/pint --dev`.

### 2. Execution

Run Pint to fix code style issues.

**Fix all files:**
```bash
./vendor/bin/pint
```

**Fix specific file(s):**
```bash
./vendor/bin/pint path/to/file.php
```

**Check without fixing (Dry Run):**
If the user only wants to *check* without modifying:
```bash
./vendor/bin/pint --test
```

### 3. Reporting

After running:
- **Success:** "Pint fixed [X] files." or "No style issues found."
- **Failure:** If syntax errors prevent Pint from running, report the specific syntax error location.

## Configuration

Pint uses `pint.json` for configuration. If the user asks to change rules (e.g., "use psr12"), check for this file or offer to create it.