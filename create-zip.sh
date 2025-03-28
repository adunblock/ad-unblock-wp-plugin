#!/bin/bash

# Remove existing zip if it exists
rm -f ad-unblock.zip

# Create a temporary directory for files to zip
TEMP_DIR=$(mktemp -d)

# Copy all non-hidden files and directories to temp directory
# Exclude .git directory, .DS_Store, and this script
rsync -av --exclude='.git/' --exclude='.DS_Store' --exclude='.gitignore' --exclude='create-zip.sh' --exclude='*.zip' ./ "$TEMP_DIR/"

# Create the zip file directly in the current directory
cd "$TEMP_DIR"
zip -r "$OLDPWD/ad-unblock.zip" .

# Clean up
cd "$OLDPWD"
rm -rf "$TEMP_DIR"

echo "Created ad-unblock.zip" 