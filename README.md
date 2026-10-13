# test-manager-library-3145556-api

Backend for *Manager Library* in PHP with Laravel 12 (T-01–T-19). Hexagonal:
pure domain in `app/Library/Domain`, use cases + ports in
`app/Library/Application`, adapters in `app/Library/Adapters`, single wiring
point in `app/Library/Bootstrap/Composition/PortBindings.php`.

## Run (via infra)

```powershell
cd ..\infra
Copy-Item .env.example .env   # set JWT_SECRET (32+ chars) and ADMIN_PASSWORD
docker compose up -d --build  # migrates, ensures the admin, serves :8000
curl http://localhost:8000/api/v1/health   # {"status":"ok"}
```

Contract: `http://localhost:8000/api/v1` per `spec-php/api-contract.md`.

## Test

```powershell
docker run --rm -v ${PWD}:/app -w /app php:8.4-cli vendor/bin/phpunit
```

50 unit tests (domain + use cases with fakes) plus `tests/Architecture`
(deptrac-style rules as PHPUnit: domain imports nothing, application never
references adapters, infrastructure is only instantiated in `PortBindings`).
