#!/bin/bash
# reset-test-db.sh — Truncates user-writable tables and resets sequences to seed state.
# Intended for local dev and CI only. Run from the project root.
# Requires the postgres container to be running (docker compose up).

set -euo pipefail

docker compose exec -T postgres psql -U elevator -d elevator <<'SQL'
TRUNCATE TABLE widgets CASCADE;
SELECT setval('widgets_id_seq', 1, false);
TRUNCATE TABLE templates CASCADE;
SELECT setval('templates_id_seq', 1, false);
TRUNCATE TABLE collections CASCADE;
SELECT setval('collections_id_seq', 1, false);
SQL
