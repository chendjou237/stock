#!/bin/bash

# Exit on error
set -e

# Configuration
REMOTE_USER="u755328260"
REMOTE_HOST="cmkdigitalinnovation.com"
REMOTE_PATH="/home/u755328260/domains/cmkdigitalinnovation.com/public_html/stocker"

# Colors for output
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo "${GREEN}Starting deployment...${NC}"

# Ensure the script is run from the project root
if [ ! -f "artisan" ]; then
    echo "Error: This script must be run from the project root"
    exit 1

fi

# Create archive of the project
echo "${GREEN}Creating project archive...${NC}"
tar --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='storage/*.key' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/framework/cache/*' \
    -czf deploy.tar.gz .

# Upload the archive
echo "${GREEN}Uploading project files...${NC}"
scp deploy.tar.gz "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/deploy.tar.gz"

# Execute deployment commands on the remote server
ssh "${REMOTE_USER}@${REMOTE_HOST}" bash -c "'cd ${REMOTE_PATH} && \
    echo \"${GREEN}Extracting archive...${NC}\" && \
    tar -xzf deploy.tar.gz && \
    rm deploy.tar.gz && \
    echo \"${GREEN}Installing composer dependencies...${NC}\" && \
    composer install --no-dev --optimize-autoloader && \
    echo \"${GREEN}Clearing cache...${NC}\" && \
    php artisan cache:clear && \
    php artisan config:clear && \
    php artisan view:clear && \
    php artisan route:clear && \
    echo \"${GREEN}Running migrations...${NC}\" && \
    php artisan migrate --force && \
    echo \"${GREEN}Optimizing...${NC}\" && \
    php artisan optimize && \
    echo \"${GREEN}Setting permissions...${NC}\" && \
    chmod -R 755 . && \
    find . -type f -exec chmod 644 {} \; && \
    chmod -R 775 storage bootstrap/cache && \
    echo \"${GREEN}Deployment completed successfully!${NC}\"'"

# Clean up local archive
rm deploy.tar.gz

echo "${GREEN}Deployment completed successfully!${NC}"