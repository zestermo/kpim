#!/bin/bash
# Deploy script for Railway
# This builds the frontend and copies it to Laravel's public folder

echo "📦 Building frontend..."
cd frontend
npm install
npm run build

echo "📋 Copying frontend to Laravel public..."
cp -r dist/* ../backend/public/

echo "✅ Frontend built and copied!"
echo ""
echo "Now push to Railway:"
echo "  cd backend"
echo "  railway up"

