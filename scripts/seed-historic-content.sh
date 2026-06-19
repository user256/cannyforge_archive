#!/usr/bin/env bash

set -euo pipefail

SITE_PATH="/var/www/html"
COUNT=120
PREFIX="Archive Test Story"
SLUG_PREFIX=""

usage() {
	cat <<'EOF'
Usage: scripts/seed-historic-content.sh [options]

Seed a WordPress install with a spread of older posts for archive/pagination testing.

Options:
  --site-path PATH   WordPress root path (default: /var/www/html)
  --count N          Number of posts to create (default: 120)
  --prefix TEXT      Post-title prefix used for generated content
  --help             Show this help
EOF
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--site-path)
			SITE_PATH="${2:?Missing value for --site-path}"
			shift 2
			;;
		--count)
			COUNT="${2:?Missing value for --count}"
			shift 2
			;;
		--prefix)
			PREFIX="${2:?Missing value for --prefix}"
			shift 2
			;;
		--help)
			usage
			exit 0
			;;
		*)
			printf 'Unknown option: %s\n\n' "$1" >&2
			usage >&2
			exit 1
			;;
	esac
done

if ! [[ "${COUNT}" =~ ^[0-9]+$ ]] || [[ "${COUNT}" -lt 1 ]]; then
	echo "--count must be a positive integer." >&2
	exit 1
fi

SLUG_PREFIX="$(
	printf '%s' "${PREFIX}" \
	| tr '[:upper:]' '[:lower:]' \
	| sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//'
)-"

WP=(wp --path="$SITE_PATH")
"${WP[@]}" core is-installed >/dev/null

ensure_user() {
	local login="$1"
	local email="$2"
	local display_name="$3"
	local user_id

	if user_id="$("${WP[@]}" user get "$login" --field=ID 2>/dev/null)"; then
		printf '%s\n' "$user_id"
		return
	fi

	"${WP[@]}" user create "$login" "$email" \
		--role=author \
		--display_name="$display_name" \
		--user_pass='archive-test-password' \
		--porcelain
}

ensure_term() {
	local taxonomy="$1"
	local name="$2"
	local slug="$3"

	if "${WP[@]}" term get "$taxonomy" "$slug" --by=slug >/dev/null 2>&1; then
		printf '%s\n' "$slug"
		return
	fi

	"${WP[@]}" term create "$taxonomy" "$name" --slug="$slug" >/dev/null
	printf '%s\n' "$slug"
}

cleanup_existing_seed_posts() {
	local prefix_sql ids
	local author_one="$1"
	local author_two="$2"
	local author_three="$3"

	prefix_sql="$("${WP[@]}" db prefix)"
	ids="$("${WP[@]}" db query \
		"SELECT ID FROM ${prefix_sql}posts WHERE post_type = 'post' AND (
			post_name LIKE '${SLUG_PREFIX}%'
			OR post_name LIKE 'archive-test-story-%'
			OR post_name LIKE 'archive-verify-story-%'
			OR post_name LIKE 'cannyforge-archive-seed-%'
			OR post_title LIKE '${PREFIX} %'
			OR post_title LIKE 'Archive Test Story %'
			OR post_title LIKE 'Archive Verify Story %'
			OR post_title LIKE 'CannyForge Archive Seed %'
			OR (
				post_author IN (${author_one}, ${author_two}, ${author_three})
				AND (post_excerpt LIKE 'Generated archive smoke post %' OR post_content LIKE 'Historic seed post %')
			)
		);" \
		--skip-column-names 2>/dev/null || true)"

	if [[ -z "${ids}" ]]; then
		return
	fi

	mapfile -t ids_array <<< "${ids}"
	if [[ "${#ids_array[@]}" -gt 0 ]]; then
		"${WP[@]}" post delete "${ids_array[@]}" --force >/dev/null
	fi
}

cleanup_numeric_terms() {
	local prefix_sql taxonomy term_ids term_id

	prefix_sql="$("${WP[@]}" db prefix)"

	for taxonomy in category post_tag; do
		term_ids="$("${WP[@]}" db query \
			"SELECT tt.term_id FROM ${prefix_sql}terms t INNER JOIN ${prefix_sql}term_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = '${taxonomy}' AND t.name REGEXP '^[0-9]+(,[0-9]+)?$';" \
			--skip-column-names 2>/dev/null || true)"

		while IFS= read -r term_id; do
			[[ -z "${term_id}" ]] && continue
			"${WP[@]}" term delete "${taxonomy}" "${term_id}" --by=id >/dev/null
		done <<< "${term_ids}"
	done
}

AUTHOR_ONE="$(ensure_user archive-tester-one archive-tester-one@example.test 'Archive Tester One')"
AUTHOR_TWO="$(ensure_user archive-tester-two archive-tester-two@example.test 'Archive Tester Two')"
AUTHOR_THREE="$(ensure_user archive-tester-three archive-tester-three@example.test 'Archive Tester Three')"

CAT_GUIDES="$(ensure_term category 'Evergreen Guides' evergreen-guides)"
CAT_NEWS="$(ensure_term category 'Industry News' industry-news)"
CAT_CASES="$(ensure_term category 'Case Studies' case-studies)"
CAT_ARCHIVE="$(ensure_term category 'Archive Experiments' archive-experiments)"

TAG_PAGINATION="$(ensure_term post_tag 'pagination' pagination)"
TAG_CRAWL="$(ensure_term post_tag 'crawl-budget' crawl-budget)"
TAG_INTERNAL="$(ensure_term post_tag 'internal-links' internal-links)"
TAG_DISCOVERY="$(ensure_term post_tag 'content-discovery' content-discovery)"
TAG_ARCHIVE="$(ensure_term post_tag 'historic-content' historic-content)"

cleanup_existing_seed_posts "$AUTHOR_ONE" "$AUTHOR_TWO" "$AUTHOR_THREE"
cleanup_numeric_terms

authors=("$AUTHOR_ONE" "$AUTHOR_TWO" "$AUTHOR_THREE")
categories=("$CAT_GUIDES" "$CAT_NEWS" "$CAT_CASES" "$CAT_ARCHIVE")
tags_a=("$TAG_PAGINATION" "$TAG_CRAWL" "$TAG_INTERNAL" "$TAG_DISCOVERY")
tags_b=("$TAG_ARCHIVE" "$TAG_INTERNAL" "$TAG_DISCOVERY" "$TAG_CRAWL")
topics=("pagination tunnels" "crawl budget" "internal links" "content discovery")

for ((i = 1; i <= COUNT; i++)); do
	index=$(( (i - 1) % 4 ))
	author_id="${authors[$(( (i - 1) % 3 ))]}"
	category_slug="${categories[$index]}"
	tag_one="${tags_a[$index]}"
	tag_two="${tags_b[$index]}"
	topic="${topics[$index]}"
	published_at="$(date -u -d "$i month ago +$(( i % 24 )) day" '+%Y-%m-%d 09:00:00')"
	title="$(printf '%s %03d' "$PREFIX" "$i")"
	slug="$(printf '%s%03d' "$SLUG_PREFIX" "$i")"
	content="Historic seed post ${i} about ${topic}. This generated content exists to test archive depth, client-side filters, and old-content discovery."
	excerpt="Generated archive smoke post ${i} covering ${topic}."

	post_id="$("${WP[@]}" post create \
		--post_type=post \
		--post_status=publish \
		--post_title="$title" \
		--post_name="$slug" \
		--post_content="$content" \
		--post_excerpt="$excerpt" \
		--post_author="$author_id" \
		--post_date="$published_at" \
		--porcelain)"

	"${WP[@]}" post term set "$post_id" category "$category_slug" >/dev/null
	"${WP[@]}" post term set "$post_id" post_tag "$tag_one" "$tag_two" >/dev/null
done

printf 'Seeded %s historic posts into %s\n' "$COUNT" "$SITE_PATH"
printf 'Archive filters are client-side JavaScript in this plugin; there is no AJAX endpoint to seed or verify.\n'
