#!/usr/bin/env bash
#
# End-to-end smoke test. Requires the stack to be running:
#
#     docker compose up --build -d
#     ./tests/smoke.sh
#
# Registers throwaway accounts and uploads files into whatever volume is
# attached, so run it against a scratch stack rather than anything you care
# about. `docker compose down -v` resets state between runs; re-running without
# a reset fails at registration because the usernames already exist.
#
# This is the only automated check in the repo. It caught the CSRF handler
# returning 419 — an unassigned status Apache rewrites to 500.
set -u
BASE="${BASE:-http://localhost:8080}"
JAR="$(mktemp)"
PASS=0; FAIL=0

ok()   { echo "  PASS  $1"; PASS=$((PASS+1)); }
bad()  { echo "  FAIL  $1"; FAIL=$((FAIL+1)); }
check(){ if [ "$1" = "$2" ]; then ok "$3"; else bad "$3 (expected '$2', got '$1')"; fi; }

# Pull the CSRF token out of a page's hidden input.
token() {
  curl -s -b "$JAR" -c "$JAR" "$BASE/$1" \
    | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | grep -o '[a-f0-9]\{16,\}'
}

echo "== anonymous access =="
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$BASE/browse.php")
check "$code" "302" "browse.php redirects anonymous users"

code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/../src/bootstrap.php")
check "$code" "404" "src/ is not reachable over HTTP"

code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api.php" -X POST -d '{}')
check "$code" "401" "api.php rejects unauthenticated POST"

echo "== register =="
T=$(token register.php)
[ -n "$T" ] && ok "register page issues a CSRF token" || bad "no CSRF token on register page"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST "$BASE/register.php" \
  -d "_token=$T" -d "username=tester" -d "password=hunter2hunter2" -d "password_confirm=hunter2hunter2")
check "$code" "302" "registration succeeds and redirects"

echo "== csrf enforcement =="
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST "$BASE/upload.php" \
  -F "_token=wrongtoken" --form-string "title=x")
check "$code" "403" "upload rejects a bad CSRF token"

echo "== upload =="
IMG=$(mktemp /tmp/XXXXXX.png)
printf '\x89PNG\r\n\x1a\n' > "$IMG"
# A real 1x1 PNG so getimagesize and finfo both succeed.
base64 -d > "$IMG" <<'B64'
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==
B64
T=$(token upload.php)
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST "$BASE/upload.php" \
  -F "_token=$T" -F "title=My test image" -F "file=@$IMG;filename=test.png;type=image/png")
check "$code" "302" "png upload accepted"

echo "== upload rejection: php disguised as png =="
EVIL=$(mktemp /tmp/XXXXXX.png)
printf '<?php echo "pwned"; ?>' > "$EVIL"
T=$(token upload.php)
body=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/upload.php" \
  -F "_token=$T" -F "title=evil" -F "file=@$EVIL;filename=evil.png;type=image/png")
echo "$body" | grep -q "does not look like" && ok "php-in-png rejected by content sniffing" \
  || bad "php-in-png was NOT rejected"

echo "== upload rejection: disallowed extension =="
SH=$(mktemp /tmp/XXXXXX.sh)
echo "echo hi" > "$SH"
T=$(token upload.php)
body=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/upload.php" \
  -F "_token=$T" -F "title=script" -F "file=@$SH;filename=run.sh;type=text/plain")
echo "$body" | grep -q "not accepted" && ok ".sh rejected by extension allowlist" \
  || bad ".sh was NOT rejected"

echo "== browse =="
body=$(curl -s -b "$JAR" -c "$JAR" "$BASE/browse.php")
echo "$body" | grep -q "My test image" && ok "uploaded file appears on browse" || bad "file missing from browse"
echo "$body" | grep -q 'kind=image' && ok "kind filter chip rendered" || bad "no kind chip"

FILE_ID=$(echo "$body" | grep -o 'data-file-id="[^"]*"' | head -1 | cut -d'"' -f2)
[ -n "$FILE_ID" ] && ok "file id exposed to the client ($FILE_ID)" || bad "no file id found"

echo "== xss =="
T=$(token upload.php)
curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST "$BASE/upload.php" \
  -F "_token=$T" --form-string 'title=<img src=x onerror=alert(1)>' -F "file=@$IMG;filename=xss.png;type=image/png"
body=$(curl -s -b "$JAR" -c "$JAR" "$BASE/browse.php")
echo "$body" | grep -q '<img src=x onerror' && bad "XSS payload rendered unescaped" \
  || ok "XSS payload escaped in browse output"
echo "$body" | grep -q '&lt;img src=x onerror' && ok "payload present but escaped" || bad "payload not found at all"

echo "== download =="
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$BASE/download.php?id=$FILE_ID")
check "$code" "200" "download returns the file"

hdrs=$(curl -s -D - -o /dev/null -b "$JAR" -c "$JAR" "$BASE/download.php?id=$FILE_ID")
echo "$hdrs" | grep -qi "content-disposition: attachment" && ok "download forced to attachment" || bad "not an attachment"
echo "$hdrs" | grep -qi "application/octet-stream" && ok "opaque content type on download" || bad "content type not opaque"

hdrs=$(curl -s -D - -o /dev/null -b "$JAR" -c "$JAR" "$BASE/download.php?id=$FILE_ID&inline=1")
echo "$hdrs" | grep -qi "content-disposition: inline" && ok "image may be served inline" || bad "inline refused for image"

echo "== path traversal =="
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$BASE/download.php?id=../../etc/passwd")
check "$code" "404" "traversal in id rejected"

echo "== api: rename =="
T=$(curl -s -b "$JAR" -c "$JAR" "$BASE/browse.php" | grep -o 'CSRF_TOKEN = "[a-f0-9]*"' | grep -o '[a-f0-9]\{16,\}')
resp=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/api.php" \
  -H "Content-Type: application/json" -H "X-CSRF-Token: $T" \
  -d "{\"action\":\"rename\",\"file_id\":\"$FILE_ID\",\"title\":\"Renamed file\"}")
echo "$resp" | grep -q '"ok":true' && ok "rename succeeds for owner" || bad "rename failed: $resp"

echo "== api: csrf on json =="
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST "$BASE/api.php" \
  -H "Content-Type: application/json" -H "X-CSRF-Token: bogus" \
  -d "{\"action\":\"delete\",\"file_id\":\"$FILE_ID\"}")
check "$code" "403" "api rejects a bad CSRF token"

echo "== ownership =="
JAR2="$(mktemp)"
T2=$(curl -s -b "$JAR2" -c "$JAR2" "$BASE/register.php" | grep -o 'name="_token" value="[a-f0-9]*"' | grep -o '[a-f0-9]\{16,\}')
curl -s -o /dev/null -b "$JAR2" -c "$JAR2" -X POST "$BASE/register.php" \
  -d "_token=$T2" -d "username=intruder" -d "password=hunter2hunter2" -d "password_confirm=hunter2hunter2"
T2=$(curl -s -b "$JAR2" -c "$JAR2" "$BASE/browse.php" | grep -o 'CSRF_TOKEN = "[a-f0-9]*"' | grep -o '[a-f0-9]\{16,\}')
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR2" -c "$JAR2" -X POST "$BASE/api.php" \
  -H "Content-Type: application/json" -H "X-CSRF-Token: $T2" \
  -d "{\"action\":\"delete\",\"file_id\":\"$FILE_ID\"}")
check "$code" "403" "another user cannot delete someone else's file"

echo "== logout =="
T=$(curl -s -b "$JAR" -c "$JAR" "$BASE/browse.php" | grep -o 'name="_token" value="[a-f0-9]*"' | grep -o '[a-f0-9]\{16,\}')
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST "$BASE/logout.php" -d "_token=$T")
check "$code" "302" "logout redirects"
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$BASE/browse.php")
check "$code" "302" "session no longer valid after logout"

echo
echo "passed: $PASS   failed: $FAIL"
[ "$FAIL" -eq 0 ]
