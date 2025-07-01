#!/bin/bash

# reverse-proxy-scanner.sh
# Corrected version that robustly parses server and location blocks from nginx configs

set -euo pipefail

OUTPUT_FILE="reverse-proxy-export-$(date +%Y%m%d%H%M%S).json"
CONFIG_FILE="${1:-/etc/nginx/sites-available/reverse_proxy}"

if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed." >&2
    exit 1
fi

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: Configuration file $CONFIG_FILE not found" >&2
    exit 1
fi

JSON_DATA=$(jq -n --arg file "$CONFIG_FILE" '{
    metadata: {
        generated_at: now | todate,
        config_file: $file,
        nginx_version: null
    },
    servers: []
}')

NGINX_VERSION=$(nginx -v 2>&1 | awk -F'/' '{print $2}' | awk '{print $1}')
if [ -n "$NGINX_VERSION" ]; then
    JSON_DATA=$(echo "$JSON_DATA" | jq --arg version "$NGINX_VERSION" '.metadata.nginx_version = $version')
fi

# State
server_block=""
location_block=""
server_lines=()
location_lines=()
in_server=false
in_location=false
brace_depth=0
location_depth=0
all_servers=()

while IFS= read -r line || [ -n "$line" ]; do
    line_clean=$(echo "$line" | sed 's/#.*$//' | xargs)
    [ -z "$line_clean" ] && continue

    if [[ "$line_clean" =~ ^server[[:space:]]*\{ ]]; then
        in_server=true
        server_lines=("$line_clean")
        brace_depth=1
        continue
    fi

    if $in_server; then
        if [[ "$line_clean" == *"{"* ]]; then ((brace_depth++)); fi
        if [[ "$line_clean" == *"}"* ]]; then ((brace_depth--)); fi
        server_lines+=("$line_clean")

        if [[ "$line_clean" =~ ^location[[:space:]]+([^[:space:]]+)[[:space:]]*\{ ]]; then
            in_location=true
            location_lines=("$line_clean")
            location_depth=1
            continue
        fi

        if $in_location; then
            if [[ "$line_clean" == *"{"* ]]; then ((location_depth++)); fi
            if [[ "$line_clean" == *"}"* ]]; then ((location_depth--)); fi
            location_lines+=("$line_clean")

            if [ "$location_depth" -eq 0 ]; then
                # Process location block
                loc_path=$(echo "${location_lines[0]}" | awk '{print $2}')
                proxy_pass=""
                params="[]"
                for l in "${location_lines[@]}"; do
                    if [[ "$l" =~ proxy_pass[[:space:]]+([^;]+)\; ]]; then
                        proxy_pass="${BASH_REMATCH[1]}"
                    elif [[ "$l" =~ ^([a-zA-Z_]+)[[:space:]]+(.+); ]]; then
                        key="${BASH_REMATCH[1]}"
                        val="${BASH_REMATCH[2]}"
                        if [ "$key" != "proxy_pass" ]; then
                            param=$(jq -n --arg name "$key" --arg value "$val" '{name: $name, value: $value}')
                            params=$(echo "$params" | jq --argjson p "$param" '. += [$p]')
                        fi
                    fi
                done
                loc_json=$(jq -n --arg path "$loc_path" --arg proxy "$proxy_pass" --argjson parameters "$params"                     '{path: $path, proxy_pass: $proxy, parameters: $parameters}')
                location_block="$location_block $loc_json"
                in_location=false
            fi
        fi

        if [ "$brace_depth" -eq 0 ]; then
            # Process server block
            server_name=""
            port="80"
            ssl="false"
            cert=""
            key=""
            client_cert=""
            verify_client="off"
            locations="[]"
            for l in "${server_lines[@]}"; do
                if [[ "$l" =~ server_name[[:space:]]+(.+) ]]; then
                    server_name=$(echo "${BASH_REMATCH[1]}" | sed 's/;$//' | xargs)
                elif [[ "$l" =~ listen[[:space:]]+([0-9]+) ]]; then
                    port="${BASH_REMATCH[1]}"
                    [[ "$l" =~ ssl ]] && ssl="true"
                elif [[ "$l" =~ ssl_certificate[[:space:]]+([^;]+)\; ]]; then
                    cert="${BASH_REMATCH[1]}"
                    ssl="true"
                elif [[ "$l" =~ ssl_certificate_key[[:space:]]+([^;]+)\; ]]; then
                    key="${BASH_REMATCH[1]}"
                    ssl="true"
                elif [[ "$l" =~ ssl_client_certificate[[:space:]]+([^;]+)\; ]]; then
                    client_cert="${BASH_REMATCH[1]}"
                elif [[ "$l" =~ ssl_verify_client[[:space:]]+([^;]+)\; ]]; then
                    verify_client="${BASH_REMATCH[1]}"
                fi
            done

            if [ -n "$location_block" ]; then
                locations=$(echo "$location_block" | jq -s '.')
            fi

            is_mtls="false"
            if [[ "$verify_client" != "off" && -n "$client_cert" ]]; then
                is_mtls="true"
            fi

            server_json=$(jq -n                 --arg name "$server_name"                 --arg port "$port"                 --argjson ssl "$ssl"                 --arg cert "$cert"                 --arg key "$key"                 --arg client_cert "$client_cert"                 --arg verify_client "$verify_client"                 --argjson is_mtls "$is_mtls"                 --argjson locations "$locations"                 '{
                    server_name: $name,
                    port: ($port | tonumber),
                    ssl_enabled: $ssl,
                    ssl_certificate: $cert,
                    ssl_certificate_key: $key,
                    ssl_client_certificate: $client_cert,
                    ssl_verify_client: $verify_client,
                    is_mtls: $is_mtls,
                    locations: $locations
                }')

            JSON_DATA=$(echo "$JSON_DATA" | jq --argjson server "$server_json" '.servers += [$server]')
            in_server=false
            location_block=""
        fi
    fi
done < "$CONFIG_FILE"

echo "$JSON_DATA" | jq . > "$OUTPUT_FILE"

server_count=$(echo "$JSON_DATA" | jq '.servers | length')
echo "Exported $server_count servers to $OUTPUT_FILE"
