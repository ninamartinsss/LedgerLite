# LedgerLite

A minimal **PHP 8.3+** personal-finance REST API backed by **SQLite**, featuring category-based expense tracking, monthly summaries and integer-cent money handling.

## Features
- Create, list and delete income/expense transactions
- Filter by month (YYYY-MM)
- Summarize income, expenses, balance and spending by category
- PDO prepared statements and SQLite persistence
- Docker and automated PHP tests

## Run
Requires PHP 8.3+ with the `pdo_sqlite` extension.

```bash
php -S 127.0.0.1:8080 -t public
```

The application creates a local `data/` folder. Override the database path via `LEDGER_DB`.

## Endpoints
| Method | Path | Function |
|---|---|---|
| GET | `/health` | Health check |
| POST | `/transactions` | Add income or expense |
| GET | `/transactions?month=2026-11` | List transactions |
| DELETE | `/transactions/{id}` | Delete transaction |
| GET | `/summary?month=2026-11` | Balance and category report |

```bash
curl -X POST http://localhost:8080/transactions -H "Content-Type: application/json" -d '{"type":"expense","amount_cents":1299,"category":"Food","description":"Lunch","occurred_on":"2026-11-22"}'
curl http://localhost:8080/summary?month=2026-11
```

Amounts use integer cents (1299 = 12.99 currency units).

## Tests
```bash
php -l src/Ledger.php
php -l public/index.php
php tests/LedgerTest.php
```

## Docker
```bash
docker build -t ledgerlite .
docker run --rm -p 127.0.0.1:8080:8080 -v ledgerlite-data:/app/data ledgerlite
```

## Security
**Single-user demonstration only.** No authentication, access control or rate limiting is implemented. Do not expose the application on a public network. Never commit personal financial data, API tokens or secrets.

## License
MIT.
