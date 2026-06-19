#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="cannyforge-archive"
DIST_DIR="${ROOT_DIR}/dist"
BUILD_DIR="${DIST_DIR}/${PLUGIN_SLUG}"
MAIN_FILE="${ROOT_DIR}/cannyforge-archive.php"
DISTIGNORE_FILE="${ROOT_DIR}/.distignore"
DEFAULT_SITE_PATH="/var/www/html"
TARGET_PLUGIN_DIR=""
DO_INSTALL=1

usage() {
	cat <<'EOF'
Usage: scripts/install-plugin.sh [options] [wp-content/plugins path]

Build the distributable plugin from the repo, create a ZIP archive, and
optionally install it into a local WordPress site.

Options:
  --build-only       Build the staged plugin and ZIP only; skip install
  --site-path PATH   WordPress root path (default: /var/www/html)
  --help             Show this help

Positional compatibility:
  /path/to/wp-content/plugins
                     Install directly into this plugins directory
EOF
}

if [[ ! -f "${MAIN_FILE}" ]]; then
	echo "Main plugin file not found: ${MAIN_FILE}" >&2
	exit 1
fi

if [[ ! -f "${DISTIGNORE_FILE}" ]]; then
	echo "Missing .distignore: ${DISTIGNORE_FILE}" >&2
	exit 1
fi

for required in rsync zip; do
	if ! command -v "${required}" >/dev/null 2>&1; then
		echo "Required command not found: ${required}" >&2
		exit 1
	fi
done

VERSION="$(
	sed -n 's/^ \{0,\}\* Version: \(.*\)$/\1/p' "${MAIN_FILE}" | head -n 1 | tr -d '\r'
)"

if [[ -z "${VERSION}" ]]; then
	echo "Could not detect plugin version from ${MAIN_FILE}" >&2
	exit 1
fi

ZIP_PATH="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
SITE_PATH="${WP_SITE_PATH:-${DEFAULT_SITE_PATH}}"

while [[ $# -gt 0 ]]; do
	case "$1" in
		--build-only)
			DO_INSTALL=0
			shift
			;;
		--site-path)
			SITE_PATH="${2:?Missing value for --site-path}"
			shift 2
			;;
		--help)
			usage
			exit 0
			;;
		*)
			if [[ -n "${TARGET_PLUGIN_DIR}" ]]; then
				echo "Only one install target may be supplied." >&2
				exit 1
			fi
			TARGET_PLUGIN_DIR="$1"
			shift
			;;
	esac
done

if [[ -z "${TARGET_PLUGIN_DIR}" && "${DO_INSTALL}" -eq 1 ]]; then
	TARGET_PLUGIN_DIR="${SITE_PATH}/wp-content/plugins"
fi

rm -rf "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

rsync -a \
	--delete \
	--exclude-from="${DISTIGNORE_FILE}" \
	"${ROOT_DIR}/" \
	"${BUILD_DIR}/"

rm -f "${ZIP_PATH}"
(
	cd "${DIST_DIR}"
	zip -rq "${ZIP_PATH}" "${PLUGIN_SLUG}"
)

if [[ "${DO_INSTALL}" -eq 1 ]]; then
	if [[ ! -d "${TARGET_PLUGIN_DIR}" ]]; then
		echo "WordPress plugins directory not found: ${TARGET_PLUGIN_DIR}" >&2
		exit 1
	fi

	mkdir -p "${TARGET_PLUGIN_DIR}"
	rsync -a --delete "${BUILD_DIR}/" "${TARGET_PLUGIN_DIR}/${PLUGIN_SLUG}/"
fi

echo "Built plugin directory: ${BUILD_DIR}"
echo "Built ZIP archive: ${ZIP_PATH}"

if [[ "${DO_INSTALL}" -eq 1 ]]; then
	echo "Installed plugin to: ${TARGET_PLUGIN_DIR}/${PLUGIN_SLUG}"
	if command -v wp >/dev/null 2>&1; then
		wp --path="${SITE_PATH}" rewrite flush || true
	fi
fi
