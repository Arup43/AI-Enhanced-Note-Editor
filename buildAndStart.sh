#!/bin/bash

# Stop and remove the existing container if it exists
docker stop ai-note-editor
docker rm ai-note-editor

# Build the Docker image with SSL support
docker build -t ai-note-editor .

# Start with updated environment
docker run -d \
  --name ai-note-editor \
  -p 80:80 \
  -p 443:443 \
  -v /etc/letsencrypt:/etc/letsencrypt:ro \
  --env-file .env \
  ai-note-editor
