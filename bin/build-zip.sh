#!/usr/bin/env bash
# Builds dist/iranian-league-table-<version>.zip with a top-level iranian-league-table/ folder.
# Excludes runtime cache files and anything listed in .distignore.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
src="$root/wp-content/plugins/iranian-league-table"
version="$(sed -n 's/^[ *]*Version:[[:space:]]*//p' "$src/iranianleaguetable.php" | tr -d '\r')"
out="$root/dist/iranian-league-table-$version.zip"
mkdir -p "$root/dist"
rm -f "$out"
python3 - "$src" "$out" "$root/.distignore" <<'PY'
import fnmatch, os, sys, zipfile
src, out, ignore_file = sys.argv[1:4]
patterns = ['data/*', '.DS_Store', '*/.DS_Store']
if os.path.exists(ignore_file):
    patterns += [l.strip() for l in open(ignore_file) if l.strip() and not l.startswith('#')]
keep = {'data/index.php'}
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for dirpath, dirs, files in os.walk(src):
        for f in sorted(files):
            rel = os.path.relpath(os.path.join(dirpath, f), src).replace(os.sep, '/')
            if rel not in keep and any(fnmatch.fnmatch(rel, p) for p in patterns):
                continue
            z.write(os.path.join(dirpath, f), 'iranian-league-table/' + rel)
    print('\n'.join(sorted(z.namelist())))
PY
echo "Built $out"
