# Repository Guidelines

## Project Structure & Module Organization
This repository is split into three runtime areas:

- `api/`: Laravel 12 backend. Core game rules live in `api/app/Domain/Rams`, HTTP endpoints in `api/app/Http/Controllers`, and persistence models in `api/app/Models`.
- `web/`: Vue 3 + Vite frontend. App entry points are `web/src/main.js` and `web/src/App.vue`; UI components live in `web/src/components`, API helpers in `web/src/api`, and state in `web/src/stores`.
- repository root: lightweight Node WebSocket bridge in `websocket-server.js` with helper launcher `start-websocket.sh`.

Backend tests are in `api/tests/Feature` and `api/tests/Unit`. Static assets for the frontend live under `web/src/assets` and `web/public`.

## Build, Test, and Development Commands
- `cd api && composer setup`: install backend dependencies, create `.env`, generate the app key, migrate, install frontend packages, and build Vite assets.
- `cd api && composer dev`: start the Laravel server, queue worker, log tail, and Vite dev server together.
- `cd api && composer test`: clear config and run the Laravel test suite.
- `cd web && npm run dev`: run the standalone frontend during UI work.
- `cd web && npm run build`: produce the production frontend bundle.
- `npm run dev`: run the root WebSocket server with `nodemon`.

## Coding Style & Naming Conventions
Follow existing style rather than introducing a new one. PHP uses PSR-12 style with 4-space indentation and class-based organization; run `cd api && ./vendor/bin/pint` before opening a PR. Vue and JavaScript files use ES modules, `camelCase` for functions and store actions, and `PascalCase` for component filenames such as `GameTable.vue`.

## Testing Guidelines
Primary automated coverage is in Laravel PHPUnit tests. Add API and domain tests beside similar files under `api/tests/Feature` or `api/tests/Unit`, using `*Test.php` naming. Run `cd api && composer test` locally before pushing. No frontend test runner is configured today, so UI-heavy changes should include manual verification notes.

## Commit & Pull Request Guidelines
Recent history favors short, imperative subjects such as `Fix tests: update ScoringTest...` and `Implement session-based game persistence and resume prompt`. Keep commits focused and descriptive. Pull requests should include a concise summary, linked issue if applicable, test results, and screenshots or short recordings for visible frontend changes.
