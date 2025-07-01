#!/bin/bash

# Function to trim whitespace from both ends of a string
trim() {
    local var="$*"
    var="${var#"${var%%[![:space:]]*}"}"  # Remove leading whitespace
    var="${var%"${var##*[![:space:]]}"}"  # Remove trailing whitespace
    echo -n "$var"
}

# Function to generate server block from JSON data
generate_nginx_config() {
    local json_file="$1"
    local output_file="$2"
    
    # Check if jq is installed
    if ! command -v jq &> /dev/null; then
        echo "Error: jq is not installed. Please install jq to run this script."
        exit 1
    fi
    
    # Check if input file exists
    if [ ! -f "$json_file" ]; then
        echo "Error: Input file $json_file does not exist."
        exit 1
    fi
    
    # Start with an empty output file
    > "$output_file"
    
    # Get the number of servers
    local server_count=$(jq '.servers | length' "$json_file")
    
    # Process each server
    for ((i=0; i<server_count; i++)); do
        local server=$(jq -c ".servers[$i]" "$json_file")
        
        # Extract server properties
        local server_name=$(echo "$server" | jq -r '.server_name' | tr -s ' ' '\n' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | grep -v '^$')
        local port=$(echo "$server" | jq -r '.port')
        local ssl_enabled=$(echo "$server" | jq -r '.ssl_enabled')
        local ssl_cert=$(trim "$(echo "$server" | jq -r '.ssl_certificate')")
        local ssl_key=$(trim "$(echo "$server" | jq -r '.ssl_certificate_key')")
        local directives=$(echo "$server" | jq -c '.directives[]?' 2>/dev/null)
        local locations=$(echo "$server" | jq -c '.locations[]?' 2>/dev/null)
        
        # Start server block
        echo "server {" >> "$output_file"
        
        # Add listen directive
        if [ "$ssl_enabled" = "true" ] || { [ "$port" = "443" ] && [ "$ssl_enabled" = "false" ]; }; then
            echo "    listen $port ssl;" >> "$output_file"
        else
            echo "    listen $port;" >> "$output_file"
        fi
        
        # Add SSL certificates if enabled and available
        if [ "$ssl_enabled" = "true" ] && [ -n "$ssl_cert" ] && [ -n "$ssl_key" ]; then
            echo "    ssl_certificate $ssl_cert;" >> "$output_file"
            echo "    ssl_certificate_key $ssl_key;" >> "$output_file"
        fi
        
        # Add server_name
        echo -n "    server_name" >> "$output_file"
        while read -r name; do
            trimmed=$(trim "$name")
            if [ -n "$trimmed" ]; then
                echo -n "     $trimmed" >> "$output_file"
            fi
        done <<< "$server_name"
        echo ";" >> "$output_file"
        echo >> "$output_file"
        
        # Add directives if any
        while IFS= read -r directive; do
            if [ -n "$directive" ]; then
                local name=$(echo "$directive" | jq -r '.name')
                local value=$(trim "$(echo "$directive" | jq -r '.value')")
                echo "    $name $value;" >> "$output_file"
            fi
        done <<< "$directives"
        
        # Add locations if any
        while IFS= read -r location; do
            if [ -n "$location" ]; then
                local path=$(echo "$location" | jq -r '.path')
                local proxy_pass=$(trim "$(echo "$location" | jq -r '.proxy_pass')")
                local loc_directives=$(echo "$location" | jq -c '.directives[]?' 2>/dev/null)
                
                echo "    location $path {" >> "$output_file"
                
                # Add proxy_pass
                if [ -n "$proxy_pass" ]; then
                    echo "        proxy_pass              $proxy_pass;" >> "$output_file"
                    echo >> "$output_file"
                fi
                
                # Add location directives
                while IFS= read -r loc_dir; do
                    if [ -n "$loc_dir" ]; then
                        local name=$(echo "$loc_dir" | jq -r '.name')
                        local value=$(trim "$(echo "$loc_dir" | jq -r '.value')")
                        echo "        $name        $value;" >> "$output_file"
                    fi
                done <<< "$loc_directives"
                
                echo "    }" >> "$output_file"
                echo >> "$output_file"
            fi
        done <<< "$locations"
        
        # Close server block
        echo "}" >> "$output_file"
        echo >> "$output_file"
    done
    
    echo "Nginx configuration has been generated in $output_file"
}

# Main script execution
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <input_json_file> <output_config_file>"
    exit 1
fi

generate_nginx_config "$1" "$2"