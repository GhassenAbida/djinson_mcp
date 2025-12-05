#!/bin/bash

# Check if version argument is provided
if [ -z "$1" ]; then
  echo "Usage: ./publish.sh <version>"
  echo "Example: ./publish.sh v1.0.0"
  exit 1
fi

VERSION=$1

# Ensure we are on main branch and up to date
echo "Syncing with remote..."
git checkout main
git pull origin main

# Tag the release
echo "Tagging release $VERSION..."
git tag $VERSION

# Push the tag
echo "Pushing tag to GitHub..."
git push origin $VERSION

echo "--------------------------------------------------"
echo "Release $VERSION tagged and pushed successfully!"
echo "--------------------------------------------------"
echo "NEXT STEPS:"
echo "1. If this is your FIRST time publishing this package:"
echo "   Go to https://packagist.org/packages/submit and submit your repo URL."
echo ""
echo "2. If you have already submitted it:"
echo "   Packagist will auto-update if you set up the GitHub Webhook."
echo "   OR you can trigger it manually with this command (replace TOKEN):"
echo ""
echo "   curl -XPOST -H'content-type:application/json' 'https://packagist.org/api/update-package?username=djinson&apiToken=YOUR_API_TOKEN' -d'{\"repository\":{\"url\":\"https://github.com/djinson/openai-mcp\"}}'"
