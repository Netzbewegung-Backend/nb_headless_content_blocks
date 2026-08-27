#!/usr/bin/env bash
#
# checkDocs.sh — link checker for relative Markdown links and anchors.
# Verifies that every relative link and every #anchor in the project's
# Markdown files (README, CONTRIBUTING, docs/, .github/) resolves.
#
# Usage: Build/Scripts/checkDocs.sh
# Exits non-zero on the first broken link/anchor found (all findings are
# reported).

set -euo pipefail

cd "$(dirname "$0")/../.."

STATUS=0
declare -a SEEN_DIRS

# All markdown files relevant for the docs (vendor/build output excluded)
mapfile -t FILES < <(find README.md CONTRIBUTING.md AGENTS.md docs .github -name '*.md' -type f 2>/dev/null | sort)

check_anchor() {
    local file="$1" anchor="$2" target="$3"
    local base="${target:-$file}"

    # normalize the anchor the same way GitHub does: lowercase, keep only
    # alphanumerics/spaces/hyphens, spaces -> dashes
    local normalized
    normalized=$(printf '%s' "${anchor#\#}" \
        | tr '[:upper:]' '[:lower:]' \
        | sed -e 's/[^[:alnum:][:space:]-]//g' -e 's/[[:space:]]\{1,\}/-/g')

    local candidate
    while IFS= read -r heading; do
        candidate=$(printf '%s' "$heading" \
            | sed -E -e 's/^[[:space:]]*#+[[:space:]]*//' \
            | tr '[:upper:]' '[:lower:]' \
            | sed -e 's/[^[:alnum:][:space:]-]//g' -e 's/[[:space:]]\{1,\}/-/g')
        if [ "$candidate" = "$normalized" ]; then
            return 0
        fi
    done < <(awk '/^```/{inblock=!inblock; next} !inblock{print}' "$base" 2>/dev/null | grep -E '^#{1,6} ' || true)

    echo "BROKEN ANCHOR: $file -> ${target:-$file}${anchor}"
    STATUS=1
    return 1
}

for file in "${FILES[@]}"; do
    dir=$(dirname "$file")

    # inline code blocks are not links; strip fenced code blocks first
    stripped=$(awk '/^```/{inblock=!inblock; next} !inblock{print}' "$file")

    # relative links: [text](path.md), [text](path#anchor), [text](../path)
    while IFS= read -r link; do
        target="${link%%#*}"
        anchor=""
        if [[ "$link" == *"#"* ]]; then
            anchor="#${link#*#}"
        fi

        if [ -z "$target" ]; then
            # pure anchor link
            check_anchor "$file" "$anchor" "" || true
            continue
        fi

        if [[ "$target" =~ ^[a-z]+: ]]; then
            continue # absolute URL (http:, mailto:, ...) — not checked
        fi

        resolved="$dir/$target"
        if [ ! -e "$resolved" ]; then
            echo "BROKEN LINK: $file -> $target"
            STATUS=1
            continue
        fi

        if [ -n "$anchor" ] && [[ "$resolved" == *.md ]]; then
            check_anchor "$file" "$anchor" "$resolved" || true
        fi
    done < <(grep -oE '\]\(([^)#]+)?(#[^)]*)?\)' <<<"$stripped" \
        | sed -e 's/^](//' -e 's/)$//' \
        | grep -v '^$')
done

if [ "$STATUS" -eq 0 ]; then
    echo "OK: all relative links and anchors in ${#FILES[@]} markdown files resolve"
else
    echo "FAILED: broken links/anchors found (see above)"
fi

exit "$STATUS"
