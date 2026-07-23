#!/usr/bin/env bash
#
# Ticket 603: boots a disposable, real WordPress instance via wp-env, installs
# and activates the built plugin from dist/, seeds historic content (reusing
# scripts/seed-historic-content.sh unmodified), and runs the real-WordPress
# integration suite (tests/WpIntegration) against it.
#
# Requires: `composer dist` already run (dist/cannyforge-archive/ built),
# Docker reachable, and Node/npm (for `npx @wordpress/env`). Run via
# `composer test:integration`.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [[ ! -d "${ROOT_DIR}/dist/cannyforge-archive" ]]; then
	echo "dist/cannyforge-archive not found — run 'composer dist' first." >&2
	exit 1
fi

export WP_ENV_CWD="${ROOT_DIR}"
export PATH="${ROOT_DIR}/scripts/wp-env-cli-shim:${PATH}"
export WP_ENV_BASE_URL="${WP_ENV_BASE_URL:-http://localhost:8891}"

SEED_COUNT="${SEED_COUNT:-48}"

cleanup() {
	echo "==> Stopping wp-env"
	npx @wordpress/env stop >/dev/null 2>&1 || true
}
trap cleanup EXIT

echo "==> Starting wp-env (WordPress + MySQL in Docker)"
npx @wordpress/env start

echo "==> Resetting the WordPress database to a clean, disposable state"
npx @wordpress/env run cli wp db reset --yes >/dev/null

echo "==> Installing WordPress"
npx @wordpress/env run cli wp core install \
	--url="${WP_ENV_BASE_URL}" \
	--title="CannyForge Archive Integration" \
	--admin_user=admin \
	--admin_password=password \
	--admin_email=admin@example.test \
	--skip-email

echo "==> Activating the plugin"
npx @wordpress/env run cli wp plugin activate cannyforge-archive

# The pagination replacement (ticket 107) hooks the classic
# `the_posts_pagination()` markup filter. Modern block themes (the WP default,
# e.g. Twenty Twenty-Five) render archive pagination via the Query Loop
# block's own `core/query-pagination`, which never calls that filter — so the
# feature is inert there (a real, previously-undiscovered gap; see the
# ticket's decisions log). Use a classic theme here so the behaviour this
# suite is required to cover is actually exercised.
echo "==> Switching to a classic theme (block themes bypass the pagination hook — see decisions log)"
npx @wordpress/env run cli wp theme activate twentyseventeen

echo "==> Setting a pretty permalink structure (the rewrite endpoint requires one)"
npx @wordpress/env run cli wp rewrite structure '/%postname%/' --hard

echo "==> Seeding historic content (scripts/seed-historic-content.sh)"
bash "${ROOT_DIR}/scripts/seed-historic-content.sh" --site-path /var/www/html --count "${SEED_COUNT}"

echo "==> Running the real-WordPress integration suite"
set +e
vendor/bin/phpunit -c phpunit.integration.xml.dist
STATUS=$?
set -e

exit "${STATUS}"
