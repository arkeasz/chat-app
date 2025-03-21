#!/bin/bash

# Verify if required programs are installed
for cmd in php nginx inotifywait; do
  if ! command -v "$cmd" &>/dev/null; then
    echo "Error: $cmd is not installed." >&2
    exit 1
  fi
done

# Define variables
PORT=8010
LOG_FILE="socket.log"

# Function to check if a port is in use and kill the process
free_port() {
  sudo fuser -k 8010/tcp
  local PIDS
  PIDS=$(pgrep -f "php socket.php")
  if [[ -n "$PIDS" ]]; then
    echo "⚠️  Encontrados procesos en ejecución, terminando... ($PIDS)"
    kill $PIDS || sudo kill -9 $PIDS
    sleep 1
  fi
}


# Function to start the PHP socket server
start_php_socket() {
  if pgrep -f "php socket.php" > /dev/null; then
    echo "✅ PHP socket server is already running."
  else
    echo "🚀 Starting PHP socket server..."
    nohup php socket.php > "$LOG_FILE" 2>&1 &
    echo "✅ PHP socket server started in the background."
  fi
}

# File monitoring with inotifywait
watch_files() {
  inotifywait -m -e modify -r -q --format '%w%f' \
    --exclude '(\.swp|\.git|\.gitignore|\.gitmodules|\.gitattributes|\.gitkeep|\.gitlab-ci\.yml|\.gitlab|socket.log)' . | \
  while read -r FILE; do
    echo "🔄 File modified: $FILE"

    # Restart Nginx only if necessary
    if ! systemctl is-active --quiet nginx; then
      echo "⚠️  Nginx is inactive, attempting to restart..."
      sudo systemctl reset-failed nginx
      sudo systemctl restart nginx
    else
      echo "✅ Nginx is already active, no restart needed."
    fi

    # Free the port if needed and restart PHP
    free_port
    start_php_socket
  done
}

# Free the port before starting the server
free_port
start_php_socket
watch_files
