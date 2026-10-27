# AGENTS.md

## Build & Test Commands

- **Static Analysis**: `./vendor/bin/phpstan analyse -c phpstan.neon`
- **Lint**: `./vendor/bin/phpcs --standard=Magento2 --warning-severity=7 .`
- **Unit Tests**: `./vendor/bin/phpunit --bootstrap vendor/autoload.php Test/Unit/`
- **Single Test**: `./vendor/bin/phpunit --bootstrap vendor/autoload.php Test/Unit/Path/To/TestFile.php`

## Architecture

Magento 2 payment module (`Clearpay_Clearpay`) for Clearpay/Afterpay BNPL (PHP 7.4+). Key directories:

- **Gateway/** - Payment gateway commands, HTTP client, request/response builders
- **Model/** - Business logic, Config, Checkout, Payment processing, GraphQL resolvers
- **Controller/** - Payment capture, Express checkout endpoints
- **Api/** - Service contracts and interfaces for REST/GraphQL
- **Observer/** - Event observers (order completion, credit memos)
- **Cron/** - Scheduled tasks (merchant config updates, offline credit memos)
- **Block/**, **ViewModel/** - Frontend widgets (CTA, Express Checkout)
- **etc/** - DI config, system.xml, routes, GraphQL schema

## Code Style

- First line: `<?php declare(strict_types=1);` (opening tag and declare on same line)
- Namespace: `Clearpay\Clearpay\*`; use typed properties and return types
- Constructor DI preferred; config paths as `public const` in Model/Config.php

## Knowledge Directory

Plans and context can be stored in `.agents/knowledge/` directories (add to .gitignore).