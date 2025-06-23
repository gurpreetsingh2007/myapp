#!/bin/bash

# Check which process is using port 8080
portApi="8080"
echo "Checking for processes on port $portApi..."
pid=$(sudo lsof -t -i :$portApi)

# Check if a process was found
if [ -n "$pid" ]; then
  echo "Found process with PID $pid. Killing it..."
  sudo kill -9 "$pid"
else
  echo "No process found on port $portApi."
fi

# Run the Go program (replace 'proxyPass.go' with the actual path if necessary)
echo "Running the Go program 'proxyPass.go'..."
nohup go run proxyPass.go > proxyPass.log 2>&1 &
tail -f proxyPass.log
