#!/bin/bash

echo "$1"


# Path to the config file
path="/etc/rsnapshot.conf"

# Backup the original content of $path
backup=$(sudo cat "$path")

# Write the new message to $path using sudo
echo "$1" | sudo tee "$path" > /dev/null

# Test Nginx configuration
if sudo rsnapshot configtest; then
  echo "Rsnapshot config test successfull. but still not saving the files"
  echo ":)"
  echo "---------------------------------------------------------------------"
  #comment the line under to save the file
  echo "$backup" | sudo tee "$path" > /dev/null
  exit 0
else
  echo "$backup" | sudo tee "$path" > /dev/null
  echo "Rsnapshot config test failed. Changes to $path were reverted."
  echo ";_;"
  echo "---------------------------------------------------------------------"

  exit 3
fi