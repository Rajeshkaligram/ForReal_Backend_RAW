#!/usr/bin/env bash
set -euo pipefail

# RentaSuit PayPal API flow smoke script
# Requirements: curl, jq

BASE_URL="${BASE_URL:-http://localhost}"
API_PREFIX="${API_PREFIX:-/api/v1.0}"
EMAIL="${EMAIL:-}"
PASSWORD="${PASSWORD:-}"
RENTED_ID="${RENTED_ID:-}"

if [[ -z "$EMAIL" || -z "$PASSWORD" || -z "$RENTED_ID" ]]; then
  echo "Usage: EMAIL=... PASSWORD=... RENTED_ID=... [BASE_URL=http://localhost] [API_PREFIX=/api/v1.0] $0"
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "jq is required but not installed."
  exit 1
fi

api_url() {
  printf "%s%s%s" "$BASE_URL" "$API_PREFIX" "$1"
}

echo "[1/4] Sign in"
LOGIN_RESPONSE=$(curl -sS -X POST "$(api_url "/signin")" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

API_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.api_token // empty')
if [[ -z "$API_TOKEN" ]]; then
  echo "Signin failed:"
  echo "$LOGIN_RESPONSE" | jq .
  exit 1
fi

echo "Token OK"

echo "[2/4] Generate payment URL"
GEN_RESPONSE=$(curl -sS -X POST "$(api_url "/checkout/generate-payment-url")" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_TOKEN" \
  -d "{\"rented_id\":$RENTED_ID}")

PAYMENT_KEY=$(echo "$GEN_RESPONSE" | jq -r '.data.payment_key // .data.pay_key // empty')
PAYMENT_URL=$(echo "$GEN_RESPONSE" | jq -r '.data.payment_url // empty')

if [[ -z "$PAYMENT_KEY" || -z "$PAYMENT_URL" ]]; then
  echo "Generate payment URL failed:"
  echo "$GEN_RESPONSE" | jq .
  exit 1
fi

echo "payment_key: $PAYMENT_KEY"
echo "payment_url: $PAYMENT_URL"
echo "Open this URL in browser and complete PayPal payment before step 3."
read -r -p "Press Enter when payment is completed..." _

echo "[3/4] Check payment status"
STATUS_RESPONSE=$(curl -sS -X POST "$(api_url "/checkout/payment-status")" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_TOKEN" \
  -d "{\"payment_key\":\"$PAYMENT_KEY\"}")

echo "$STATUS_RESPONSE" | jq .
STATUS_CODE=$(echo "$STATUS_RESPONSE" | jq -r '.status // 0')
if [[ "$STATUS_CODE" != "200" ]]; then
  echo "Payment is not confirmed by PayPal yet."
  exit 1
fi

echo "[4/4] Proceed to payment"
PROCEED_RESPONSE=$(curl -sS -X POST "$(api_url "/proceed-to-payment")" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_TOKEN" \
  -d "{\"rented_id\":$RENTED_ID,\"pay_key\":\"$PAYMENT_KEY\"}")

echo "$PROCEED_RESPONSE" | jq .
PROCEED_CODE=$(echo "$PROCEED_RESPONSE" | jq -r '.status // 0')
if [[ "$PROCEED_CODE" != "200" ]]; then
  echo "Proceed-to-payment failed."
  exit 1
fi

echo "Flow completed successfully."
