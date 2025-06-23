#!/bin/bash

#echo "$1"


# Path to the config file
path="/etc/nginx/sites-available/reverse_proxy"

# Backup the original content of $path
backup=$(sudo cat "$path")

# Write the new message to $path using sudo
echo "$1" | sudo tee "$path" > /dev/null

# Test Nginx configuration
if sudo nginx -t; then
  sudo systemctl restart nginx
  #sudo service nginx restart
  echo ":)"
  echo "---------------------------------------------------------------------"
  exit 0
else
  echo "$backup" | sudo tee "$path" > /dev/null
  echo "Nginx configuration test failed. Changes to $path were reverted."
  echo ";_;"
  echo "---------------------------------------------------------------------"

  exit 3
fi