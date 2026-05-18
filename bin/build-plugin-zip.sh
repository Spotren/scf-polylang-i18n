#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="scf-polylang-i18n"
OUTPUT_PATH="${ROOT_DIR}/${PLUGIN_SLUG}.zip"

cd "${ROOT_DIR}"
rm -f "${OUTPUT_PATH}"

git archive --format=zip --worktree-attributes --output="${OUTPUT_PATH}" --prefix="${PLUGIN_SLUG}/" HEAD

printf 'Built %s\n' "${OUTPUT_PATH}"
