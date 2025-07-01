#!/bin/bash

# Enhanced NGINX Configuration Scanner
# More robust version with better error handling and performance

set -o pipefail
shopt -s nullglob

# Check root
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root" >&2
    exit 1
fi

# Check dependencies
check_dependency() {
    if ! command -v "$1" &> /dev/null; then
        echo "Installing $1..."
        apt-get update >/dev/null && apt-get install -y "$1" >/dev/null || {
            echo "Failed to install $1" >&2
            exit 1
        }
    fi
}

check_dependency jq
check_dependency openssl
check_dependency nginx

# Setup
OUTPUT_FILE="/tmp/nginx_config_scan.json"
TEMP_DIR=$(mktemp -d)
trap 'rm -rf "$TEMP_DIR"' EXIT

# Helper Functions
safe_content() {
    [ -f "$1" ] && jq -Rs . < "$1" || echo "null"
}

validate_nginx() {
    local file="$1"
    local temp_file="$TEMP_DIR/nginx_test_$(basename "$file").txt"
    
    if nginx -t -c "$file" &> "$temp_file"; then
        echo '{"valid":true,"errors":null}'
    else
        local errors
        errors=$(jq -Rs . < "$temp_file")
        echo "{\"valid\":false,\"errors\":$errors}"
    fi
}

get_file_info() {
    local file="$1"
    local resolved_file
    resolved_file=$(readlink -f "$file" 2>/dev/null || echo "$file")
    
    # Skip binary files
    if file "$resolved_file" | grep -q "binary"; then
        return
    fi
    
    local validation
    validation=$(validate_nginx "$resolved_file")
    local valid
    valid=$(echo "$validation" | jq '.valid')
    local errors
    errors=$(echo "$validation" | jq '.errors')
    
    jq -n \
        --arg path "$file" \
        --arg original "$resolved_file" \
        --arg name "$(basename "$file")" \
        --arg size "$(stat -c %s "$resolved_file" 2>/dev/null || echo "0")" \
        --arg hash "$(sha256sum "$resolved_file" 2>/dev/null | awk '{print $1}' || echo "")" \
        --arg content "$(safe_content "$resolved_file")" \
        --arg type "$(
            if [[ "$file" == *nginx.conf ]]; then echo "main";
            elif [[ "$file" == *snippets* ]]; then echo "snippet";
            elif [[ "$file" == *sites-available* ]] || [[ "$file" == *conf.d* ]]; then echo "server";
            else echo "config"; fi
        )" \
        --argjson symlink "$([ -L "$file" ] && echo true || echo false)" \
        --argjson valid "$valid" \
        --argjson errors "$errors" \
        --arg modified "$(date -u -r "$resolved_file" +"%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "unknown")" \
        '{
            file_path: $path,
            original_path: $original,
            filename: $name,
            file_size: ($size | tonumber),
            file_hash: $hash,
            file_content: $content,
            file_type: $type,
            is_enabled: true,
            is_symlink: $symlink,
            syntax_valid: $valid,
            syntax_errors: $errors,
            last_modified: $modified,
            servers: []
        }'
}

parse_server_blocks() {
    local file="$1"
    local in_block=false
    local brace_level=0
    local start_line=0
    local -a servers
    
    while IFS= read -r line; do
        if [[ "$line" =~ ^[[:space:]]*server[[:space:]]*\{ ]]; then
            in_block=true
            brace_level=1
            start_line="$((++line_number))"
            server_block="$line"
            continue
        fi
        
        if $in_block; then
            server_block+=$'\n'"$line"
            
            # Count braces
            brace_level=$((brace_level + $(grep -o '{' <<< "$line" | wc -l) - $(grep -o '}' <<< "$line" | wc -l)))
            
            if [ "$brace_level" -eq 0 ]; then
                in_block=false
                
                # Parse server directives
                server_name=$(awk '/server_name/ {print $2}' <<< "$server_block" | tr -d ';' | xargs || echo "")
                listen=$(awk '/listen/ {print $2}' <<< "$server_block" | tr -d ';' | tr '\n' ',' | sed 's/,$//' || echo "")
                root=$(awk '/root/ {print $2}' <<< "$server_block" | tr -d ';' | xargs || echo "")
                ssl_cert=$(awk '/ssl_certificate/ {print $2}' <<< "$server_block" | tr -d ';' | xargs || echo "")
                ssl_key=$(awk '/ssl_certificate_key/ {print $2}' <<< "$server_block" | tr -d ';' | xargs || echo "")
                
                # Create server JSON
                servers+=("$(jq -n \
                    --arg name "$server_name" \
                    --arg listen "$listen" \
                    --arg root "$root" \
                    --arg ssl_cert "$ssl_cert" \
                    --arg ssl_key "$ssl_key" \
                    '{
                        server_name: $name,
                        listen_ports: $listen,
                        root_directory: $root,
                        ssl_certificate: $ssl_cert,
                        ssl_certificate_key: $ssl_key,
                        locations: []
                    }')")
            fi
        fi
        
        ((line_number++))
    done < <(cat -n "$file")
    
    [ ${#servers[@]} -gt 0 ] && printf '%s\n' "${servers[@]}" | jq -s . || echo "[]"
}

process_ssl_certs() {
    local -a processed_certs
    local -a cert_paths
    local -a cert_jsons
    
    # Find all potential cert files in /etc/nginx
    while IFS= read -r -d '' cert; do
        cert_paths+=("$cert")
    done < <(find /etc/nginx -type f \( -name "*.crt" -o -name "*.pem" -o -name "*.cer" -o -name "*.key" \) -print0)
    
    # Find certs referenced in configs
    while IFS= read -r cert; do
        # Resolve relative paths
        if [[ "$cert" != /* ]]; then
            cert="/etc/nginx/$cert"
        fi
        cert=$(readlink -f "$cert" || echo "$cert")
        [ -f "$cert" ] && cert_paths+=("$cert")
    done < <(grep -rho "ssl_certificate[[:space:]]\+[^;]\+" /etc/nginx 2>/dev/null | awk '{print $2}' | sort -u)
    
    # Process unique certs
    while IFS= read -r -d '' cert; do
        # Skip if already processed
        [[ " ${processed_certs[@]} " =~ " ${cert} " ]] && continue
        
        # Skip if not a certificate file
        if [[ "$cert" == *.key ]]; then
            continue
        fi
        
        local info
        info=$(openssl x509 -in "$cert" -noout -text 2>/dev/null)
        if [ -z "$info" ]; then
            continue
        fi
        
        local cert_json
        cert_json=$(jq -n \
            --arg path "$cert" \
            --arg name "$(basename "$cert")" \
            --arg subject "$(echo "$info" | grep -m1 "Subject:" | sed 's/Subject: //')" \
            --arg issuer "$(echo "$info" | grep -m1 "Issuer:" | sed 's/Issuer: //')" \
            --arg sha1 "$(openssl x509 -in "$cert" -noout -fingerprint -sha1 | sed 's/SHA1 Fingerprint=//')" \
            --arg not_after "$(echo "$info" | grep -m1 "Not After" | sed 's/.*Not After : //')" \
            --arg not_before "$(echo "$info" | grep -m1 "Not Before" | sed 's/.*Not Before : //')" \
            '{
                certificate_name: $name,
                certificate_path: $path,
                subject: $subject,
                issuer: $issuer,
                fingerprint_sha1: $sha1,
                validation: {
                    not_before: $not_before,
                    not_after: $not_after
                }
            }')
        
        if [ -n "$cert_json" ]; then
            cert_jsons+=("$cert_json")
            processed_certs+=("$cert")
        fi
    done < <(printf '%s\0' "${cert_paths[@]}" | sort -zu)
    
    # Process keys separately
    while IFS= read -r -d '' key; do
        [[ " ${processed_certs[@]} " =~ " ${key} " ]] && continue
        
        local key_json
        key_json=$(jq -n \
            --arg path "$key" \
            --arg name "$(basename "$key")" \
            --arg algorithm "$(openssl rsa -in "$key" -noout -text 2>/dev/null | grep -m1 "Public-Key:" | sed 's/Public-Key: //')" \
            --arg bits "$(openssl rsa -in "$key" -noout -text 2>/dev/null | grep -m1 "bit" | awk '{print $1}')" \
            '{
                certificate_name: $name,
                certificate_path: $path,
                key_info: {
                    algorithm: $algorithm,
                    bits: $bits
                }
            }')
        
        [ -n "$key_json" ] && cert_jsons+=("$key_json")
    done < <(find /etc/nginx -type f -name "*.key" -print0)
    
    [ ${#cert_jsons[@]} -gt 0 ] && printf '%s\n' "${cert_jsons[@]}" | jq -s 'unique_by(.certificate_path)' || echo "[]"
}

# Main Scan
echo "Starting enhanced NGINX scan..."
echo "Scan started at: $(date)"

# Process all config files
config_files=()
while IFS= read -r -d '' file; do
    echo "Processing: $file"
    file_json=$(get_file_info "$file")
    [ -n "$file_json" ] && config_files+=("$file_json")
done < <(find /etc/nginx -type f \( -name "*.conf" -o -name "*.snippet" -o -path "*/sites-available/*" -o -path "*/conf.d/*" \) -print0)

# Process SSL certs
ssl_certs_json=$(process_ssl_certs)

# Construct final JSON
echo "Generating final report..."
JSON_DATA=$(jq -n \
    --argjson config_files "$(printf '%s\n' "${config_files[@]}" | jq -s .)" \
    --argjson ssl_certs "$ssl_certs_json" \
    '{
        scan: {
            scan_date: now|strftime("%Y-%m-%d %H:%M:%S"),
            scan_path: "/etc/nginx",
            scan_status: "completed",
            total_files: ($config_files | length),
            total_servers: ([$config_files[].servers[]] | length),
            total_certificates: ($ssl_certs | length)
        },
        config_files: $config_files,
        ssl_certificates: $ssl_certs
    }')

# Save output
echo "$JSON_DATA" > "$OUTPUT_FILE"
echo "Scan complete. Results saved to $OUTPUT_FILE"

exit 0