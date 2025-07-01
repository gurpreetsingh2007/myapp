#!/bin/bash

# Script to scan /etc/nginx/ssl for certificate-key pairs and generate a JSON report

OUTPUT_FILE="certificates.json"
SSL_BASE_DIR="/etc/nginx/ssl"

# Initialize JSON array
echo "[" > "$OUTPUT_FILE"
first_entry=true

# Function to extract modulus fingerprint (RSA or EC)
get_key_fingerprint() {
    local key="$1"
    openssl pkey -in "$key" -pubout -outform DER 2>/dev/null | openssl dgst -md5 | awk '{print $2}'
}

get_cert_fingerprint() {
    local cert="$1"
    openssl x509 -in "$cert" -pubkey -noout | openssl pkey -pubin -outform DER 2>/dev/null | openssl dgst -md5 | awk '{print $2}'
}

# Function to extract certificate details
extract_cert_info() {
    local cert_path="$1"
    local key_path="$2"

    cert_info=$(openssl x509 -in "$cert_path" -noout -text 2>/dev/null)
    if [[ -z "$cert_info" ]]; then
        echo "Warning: Could not parse certificate: $cert_path" >&2
        return 1
    fi

    # Validate key match (supports RSA, EC, PKCS8)
    cert_fingerprint=$(get_cert_fingerprint "$cert_path")
    key_fingerprint=""
    if [[ -n "$key_path" && -f "$key_path" ]]; then
        key_fingerprint=$(get_key_fingerprint "$key_path")
        if [[ "$cert_fingerprint" != "$key_fingerprint" ]]; then
            echo "Warning: Key does not match certificate: $key_path vs $cert_path" >&2
            key_path=""
        fi
    fi
    echo "$cert_info --------------------"
    # Extract certificate fields
    subject=$(echo "$cert_info" | grep -m1 "Subject:" | sed 's/Subject: //')
    issuer=$(echo "$cert_info" | grep -m1 "Issuer:" | sed 's/Issuer: //')
    valid_from=$(echo "$cert_info" | grep -m1 "Not Before:" | sed 's/Not Before: //')
    valid_to=$(echo "$cert_info" | grep -m1 "Not After :" | sed 's/Not After : //')
    serial_number=$(echo "$cert_info" | awk '/Serial Number:/ { getline; gsub(/^[ \t]+/, ""); print }')
    fingerprint=$(openssl x509 -in "$cert_path" -noout -fingerprint | cut -d'=' -f2)
    algorithm=$(echo "$cert_info" | grep -m1 "Public Key Algorithm:" | sed 's/Public Key Algorithm: //')
    key_size=$(echo "$cert_info" | grep -m1 "RSA Public-Key" | awk '{print $3}' | tr -d '()')
    [[ -z "$key_size" ]] && key_size=$(echo "$cert_info" |   grep -m1 "Public-Key:" | awk '{print $2}' | tr -d '()')

    is_self_signed=false
    [[ "$issuer" == "$subject" ]] && is_self_signed=true

    cert_name=$(basename "$cert_path" | sed 's/\.[^.]*$//')

    # Append JSON entry
    [[ "$first_entry" == false ]] && echo "," >> "$OUTPUT_FILE" || first_entry=false

    cat <<EOF >> "$OUTPUT_FILE"
{
    "cert_name": "$cert_name",
    "cert_path": "$cert_path",
    "key_path": "$key_path",
    "issuer": "$issuer",
    "subject": "$subject",
    "valid_from": "$valid_from",
    "valid_to": "$valid_to",
    "serial_number": "$serial_number",
    "fingerprint": "$fingerprint",
    "algorithm": "$algorithm",
    "key_size": "$key_size",
    "is_self_signed": $is_self_signed
}
EOF
}

# Collect certs and keys
mapfile -d '' cert_files < <(find "$SSL_BASE_DIR" -type f \( -iname "*.crt" -o -iname "*.cer" -o -iname "*.pem" \) ! -iname "*.key" -print0)
mapfile -d '' key_files < <(find "$SSL_BASE_DIR" -type f \( -iname "*.key" -o -iname "*-key.pem" \) -print0)

# Track matched keys
declare -A matched_keys

# Try fingerprint or filename matching
for cert in "${cert_files[@]}"; do
    matched=""
    cert_fingerprint=$(get_cert_fingerprint "$cert")

    for key in "${key_files[@]}"; do
        [[ -n "${matched_keys["$key"]}" ]] && continue

        key_fingerprint=$(get_key_fingerprint "$key")
        if [[ "$cert_fingerprint" == "$key_fingerprint" ]]; then
            extract_cert_info "$cert" "$key"
            matched_keys["$key"]=1
            matched="yes"
            break
        fi

        # Fallback match by filename
        cert_base=$(basename "$cert" | sed 's/\.[^.]*$//')
        key_base=$(basename "$key" | sed 's/\.[^.]*$//')
        if [[ "$cert_base" == "$key_base" ]]; then
            echo "Fallback match by name: $cert_base" >&2
            extract_cert_info "$cert" "$key"
            matched_keys["$key"]=1
            matched="yes"
            break
        fi
    done

    [[ -z "$matched" ]] && extract_cert_info "$cert" ""
done

# Warn about unused keys
for key in "${key_files[@]}"; do
    [[ -z "${matched_keys["$key"]}" ]] && echo "Unmatched key: $key" >&2
done

# Finalize JSON
echo "]" >> "$OUTPUT_FILE"
echo "? Certificate report saved to $OUTPUT_FILE"
