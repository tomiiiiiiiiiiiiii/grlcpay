#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/tmp/grlcpay-e2e"
BALANCE_FILE="/tmp/grlcpay-mock-balance"
APP_LOG="/tmp/grlcpay-app.log"
EXPLORER_LOG="/tmp/grlcpay-explorer.log"
ADDR="G111111111111111111111111111111111"
AMOUNT="1.25"
SECRET="E2E-SECRET-OK"

rm -rf "$APP_DIR"
mkdir -p "$APP_DIR/data"
cp index.php "$APP_DIR/index.php"

# Patch only the temporary test copy. Production source remains unchanged.
python3 - <<'PY'
from pathlib import Path
p = Path("/tmp/grlcpay-e2e/index.php")
s = p.read_text()
s = s.replace(
    "https://explorer.grlc.eu/addr.php?&api=1&op=balance&a=",
    "http://127.0.0.1:18081/explorer1?address="
)
p.write_text(s)
PY

echo "0" > "$BALANCE_FILE"

GRLCPAY_MOCK_BALANCE_FILE="$BALANCE_FILE" php -S 127.0.0.1:18081 .github/tests/mock-explorer.php >"$EXPLORER_LOG" 2>&1 &
EXPLORER_PID=$!

GRLCPAY_ENCRYPTION_KEY="0123456789abcdef0123456789abcdef" php -S 127.0.0.1:18080 -t "$APP_DIR" >"$APP_LOG" 2>&1 &
APP_PID=$!

cleanup() {
    kill "$APP_PID" "$EXPLORER_PID" >/dev/null 2>&1 || true
}
trap cleanup EXIT

for i in $(seq 1 40); do
    if curl -fsS "http://127.0.0.1:18080/index.php" >/dev/null 2>&1 &&
       curl -fsS "http://127.0.0.1:18081/test" >/dev/null 2>&1; then
        break
    fi
    sleep 0.25
done

curl -fsS -X POST     --data-urlencode "pid=add"     --data-urlencode "amount=$AMOUNT"     --data-urlencode "addr=$ADDR"     --data-urlencode "email="     --data-urlencode "code=$SECRET"     "http://127.0.0.1:18080/index.php" > /tmp/grlcpay-created.html

grep -q "Your new GRLC payment link" /tmp/grlcpay-created.html

LINK=$(grep -oE 'index\.php\?q=[a-f0-9]{32}' /tmp/grlcpay-created.html | head -n1)
if [ -z "$LINK" ]; then
    echo "Could not extract payment link" >&2
    cat /tmp/grlcpay-created.html >&2
    exit 1
fi

curl -fsSL "http://127.0.0.1:18080/$LINK" > /tmp/grlcpay-waiting.html
grep -q "Status: waiting for payment" /tmp/grlcpay-waiting.html
if grep -q "$SECRET" /tmp/grlcpay-waiting.html; then
    echo "Secret leaked before payment" >&2
    exit 1
fi

echo "$AMOUNT" > "$BALANCE_FILE"

curl -fsSL "http://127.0.0.1:18080/$LINK" > /tmp/grlcpay-paid.html
grep -q "Payment completed!" /tmp/grlcpay-paid.html
grep -q "$SECRET" /tmp/grlcpay-paid.html

curl -fsSL "http://127.0.0.1:18080/$LINK" > /tmp/grlcpay-reuse.html
grep -q "Payment link not found or already used" /tmp/grlcpay-reuse.html

# A non-empty address must no longer be accepted as a fresh payment address.
curl -fsS -X POST     --data-urlencode "pid=add"     --data-urlencode "amount=$AMOUNT"     --data-urlencode "addr=$ADDR"     --data-urlencode "email="     --data-urlencode "code=SECOND-SECRET"     "http://127.0.0.1:18080/index.php" > /tmp/grlcpay-reject-used.html
grep -q "Fill out all fields correctly" /tmp/grlcpay-reject-used.html

echo "HTTP E2E OK: create -> waiting -> paid -> secret -> one-time reuse blocked -> used address rejected"
