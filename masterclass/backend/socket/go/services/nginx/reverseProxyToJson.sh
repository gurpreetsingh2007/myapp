#!/bin/bash

# reverse-proxy-scanner.sh
# Robust parser for Nginx reverse proxy configurations

OUTPUT_FILE="reverse-proxy-export.json"
CONFIG_FILE="/etc/nginx/sites-available/reverse_proxy"

# Check if config file exists
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: Configuration file $CONFIG_FILE not found" >&2
    exit 1
fi

# Initialize JSON structure
JSON_DATA=$(jq -n \
    --arg config_file "$CONFIG_FILE" \
    --arg generated_at "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" \
    --arg nginx_version "$(nginx -v 2>&1 | awk -F'/' '{print $2}')" \
    '{
        metadata: {
            generated_at: $generated_at,
            config_file: $config_file,
            nginx_version: $nginx_version
        },
        servers: []
    }'
)
SERVER_COUNT=0
LOCATION_COUNT=0

echo "Scanning reverse proxy configuration: $CONFIG_FILE"

# State variables
current_server=""
current_location=""
context_stack=()
in_server_block=false
in_location_block=false
server_brace_count=0
location_brace_count=0
directive_buffer=""

# Function to clean and trim a line
clean_line() {
    local line=$1
    # Remove comments and trim
    line=${line%%#*}
    line="${line%"${line##*[![:space:]]}"}"
    line="${line#"${line%%[![:space:]]*}"}"
    echo "$line"
}

# Function to parse a directive
parse_directive() {
    local line=$1
    local context=$2
    
    # Handle multi-line directives
    if [[ "$line" == *";"* && -z "$directive_buffer" ]]; then
        # Complete directive on one line
        directive="${line%;}"
    elif [ -n "$directive_buffer" ]; then
        # Continue multi-line directive
        directive_buffer+=" $line"
        if [[ "$line" == *";"* ]]; then
            directive="${directive_buffer%;}"
            directive_buffer=""
        else
            return
        fi
    else
        # Start of multi-line directive
        directive_buffer="$line"
        return
    fi

    # Split directive into param_name and param_value
    local param_name="${directive%% *}"
    local param_value="${directive#* }"
    
    # Skip empty directives
    if [[ -z "$param_name" ]]; then
        return
    fi

    # Handle different contexts
    case "$context" in
        server)
            case "$param_name" in
                server_name)
                    current_server=$(echo "$current_server" | jq --arg param_name "$param_value" '.server_name = $param_name')
                    ;;
                listen)
                    # Extract port and check for SSL
                    if [[ "$param_value" =~ ([0-9]+) ]]; then
                        port="${BASH_REMATCH[1]}"
                        current_server=$(echo "$current_server" | jq --arg port "$port" '.port = ($port | tonumber)')
                    fi
                    if [[ "$param_value" == *"ssl"* ]]; then
                        current_server=$(echo "$current_server" | jq '.ssl_enabled = true')
                    else
                        current_server=$(echo "$current_server" | jq '.ssl_enabled = false')
                    fi
                    ;;
                ssl_certificate)
                    current_server=$(echo "$current_server" | jq --arg param_value "$param_value" '.ssl_certificate = $param_value')
                    ;;
                ssl_certificate_key)
                    current_server=$(echo "$current_server" | jq --arg param_value "$param_value" '.ssl_certificate_key = $param_value')
                    ;;
                ssl_client_certificate)
                    current_server=$(echo "$current_server" | jq --arg param_value "$param_value" '.ssl_client_certificate = $param_value')
                    ;;
                ssl_verify_client)
                    current_server=$(echo "$current_server" | jq --arg param_value "$param_value" '.ssl_verify_client = $param_value')
                    if [[ "$param_value" != "off" ]]; then
                        current_server=$(echo "$current_server" | jq '.is_mtls = true')
                    else
                        current_server=$(echo "$current_server" | jq '.is_mtls = false')
                    fi
                    ;;
                add_header)
                    # Skip HSTS headers for now
                    if [[ ! "$param_value" == "Strict-Transport-Security"* ]]; then
                        current_server=$(echo "$current_server" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    fi
                    ;;
                return)
                    # Handle return directives (like in HTTP to HTTPS redirects)
                    current_server=$(echo "$current_server" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
                root)
                    current_server=$(echo "$current_server" | jq --arg param_value "$param_value" '.root = $param_value')
                    ;;
                index)
                    current_server=$(echo "$current_server" | jq --arg param_value "$param_value" '.index = $param_value')
                    ;;
                *)
                    # Capture other server-level directives
                    current_server=$(echo "$current_server" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
            esac
            ;;
        location)
            case "$param_name" in
                proxy_pass)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.proxy_pass = $param_value')
                    ;;
                fastcgi_pass)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.fastcgi_pass = $param_value')
                    ;;
                if)
                    # Special handling for if blocks
                    current_location=$(echo "$current_location" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
                add_header)
                    # Skip HSTS headers for now
                    if [[ ! "$param_value" == "Strict-Transport-Security"* ]]; then
                        current_location=$(echo "$current_location" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    fi
                    ;;
                return)
                    # Handle return directives
                    current_location=$(echo "$current_location" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
                root)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.root = $param_value')
                    ;;
                default_type)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.default_type = $param_value')
                    ;;
                try_files)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.try_files = $param_value')
                    ;;
                deny)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.deny = $param_value')
                    ;;
                include)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.include = $param_value')
                    ;;
                fastcgi_param)
                    current_location=$(echo "$current_location" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.fastcgi_params += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
                fastcgi_index)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.fastcgi_index = $param_value')
                    ;;
                fastcgi_split_path_info)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.fastcgi_split_path_info = $param_value')
                    ;;
                fastcgi_read_timeout)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.fastcgi_read_timeout = $param_value')
                    ;;
                access_log)
                    current_location=$(echo "$current_location" | jq --arg param_value "$param_value" '.access_log = $param_value')
                    ;;
                set)
                    current_location=$(echo "$current_location" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.set_directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
                *)
                    # Capture all other location directives
                    current_location=$(echo "$current_location" | jq --arg param_name "$param_name" --arg param_value "$param_value" '.directives += [{"param_name": $param_name, "param_value": $param_value}]')
                    ;;
            esac
            ;;
    esac
}

# Function to finalize server block
finalize_server() {
    if [ -n "$current_server" ]; then
        # Add server to JSON if it has locations or is a redirect
        local locations=$(echo "$current_server" | jq '.locations | length')
        local has_redirect=$(echo "$current_server" | jq '.directives[] | select(.param_name == "return")' | jq -s 'length')
        local has_root=$(echo "$current_server" | jq '.root != ""')
        
        if [ "$locations" -gt 0 ] || [ "$has_redirect" -gt 0 ] || [ "$has_root" == "true" ]; then
            JSON_DATA=$(echo "$JSON_DATA" | jq --argjson server "$current_server" '.servers += [$server]')
            ((SERVER_COUNT++))
        fi
        current_server=""
    fi
}

# Function to finalize location block
finalize_location() {
    if [ -n "$current_location" ]; then
        # Add location to server if it has proxy_pass, fastcgi_pass, or is a redirect
        local proxy_pass=$(echo "$current_location" | jq -r '.proxy_pass')
        local fastcgi_pass=$(echo "$current_location" | jq -r '.fastcgi_pass')
        local has_redirect=$(echo "$current_location" | jq '.directives[] | select(.param_name == "return")' | jq -s 'length')
        local has_root=$(echo "$current_location" | jq '.root != ""')
        local has_deny=$(echo "$current_location" | jq '.deny != ""')
        
        if { [ -n "$proxy_pass" ] && [ "$proxy_pass" != "null" ]; } || 
           { [ -n "$fastcgi_pass" ] && [ "$fastcgi_pass" != "null" ]; } || 
           [ "$has_redirect" -gt 0 ] || [ "$has_root" == "true" ] || [ "$has_deny" == "true" ]; then
            current_server=$(echo "$current_server" | jq --argjson location "$current_location" '.locations += [$location]')
            ((LOCATION_COUNT++))
        fi
        current_location=""
    fi
}

# Initialize server template
SERVER_TEMPLATE=$(jq -n '{
    server_name: "",
    port: 80,
    ssl_enabled: false,
    ssl_certificate: "",
    ssl_certificate_key: "",
    ssl_client_certificate: "",
    ssl_verify_client: "off",
    is_mtls: false,
    directives: [],
    locations: []
}')

# Initialize location template
LOCATION_TEMPLATE=$(jq -n '{
    path: "",
    proxy_pass: "",
    directives: []
}')


# Main parsing loop
while IFS= read -r line || [ -n "$line" ]; do
    # Clean and trim the line
    line_clean=$(clean_line "$line")
    
    # Skip empty lines
    if [ -z "$line_clean" ]; then
        continue
    fi
    
    # Handle multi-line directives
    if [ -n "$directive_buffer" ]; then
        parse_directive "$line_clean" "${context_stack[-1]}"
        continue
    fi
    
    # Detect server block start
    if [[ "$line_clean" == "server"* ]] && [[ "$line_clean" == *"{"* ]]; then
        # Finalize previous server if any
        finalize_server
        
        # Initialize new server block
        in_server_block=true
        server_brace_count=1
        context_stack+=("server")
        current_server=$(echo "$SERVER_TEMPLATE" | jq .)
        
        # Handle server_name if provided on same line
        if [[ "$line_clean" =~ server_name[[:space:]]+(.*)[[:space:]]*\{ ]]; then
            current_server_name="${BASH_REMATCH[1]}"
            current_server=$(echo "$current_server" | jq --arg param_name "$current_server_name" '.server_name = $param_name')
        fi
        continue
    fi
    
    # Detect location block start
    if $in_server_block && [[ "$line_clean" == "location"* ]] && [[ "$line_clean" == *"{"* ]]; then
        in_location_block=true
        location_brace_count=1
        context_stack+=("location")
        
        # Extract location path
        if [[ "$line_clean" =~ location[[:space:]]+([^{[:space:]]+)[[:space:]]*\{ ]]; then
            location_path="${BASH_REMATCH[1]}"
            current_location=$(echo "$LOCATION_TEMPLATE" | jq --arg path "$location_path" '.path = $path')
        fi
        continue
    fi
    
    # Count opening braces
    if [[ "$line_clean" == *"{"* ]]; then
        if $in_location_block; then
            ((location_brace_count++))
        elif $in_server_block; then
            ((server_brace_count++))
        fi
    fi
    
    # Count closing braces
    if [[ "$line_clean" == *"}"* ]]; then
        if $in_location_block; then
            ((location_brace_count--))
            # End of location block
            if [ $location_brace_count -eq 0 ]; then
                in_location_block=false
                context_stack=("${context_stack[@]::${#context_stack[@]}-1}") # Pop context
                finalize_location
            fi
        elif $in_server_block; then
            ((server_brace_count--))
            # End of server block
            if [ $server_brace_count -eq 0 ]; then
                in_server_block=false
                context_stack=() # Reset context
                finalize_server
            fi
        fi
        continue
    fi
    
    # Parse directives in current context
    if [ ${#context_stack[@]} -gt 0 ]; then
        parse_directive "$line_clean" "${context_stack[-1]}"
    fi
done < "$CONFIG_FILE"

# Finalize any remaining blocks
finalize_location
finalize_server

# Save output
echo "$JSON_DATA" | jq . > "$OUTPUT_FILE"

if [ $SERVER_COUNT -gt 0 ]; then
    echo "Successfully exported $SERVER_COUNT server block(s) with $LOCATION_COUNT location(s) to $OUTPUT_FILE"
    exit 0
else
    echo "No server blocks with proxy configurations found in $CONFIG_FILE" >&2
    exit 1
fi