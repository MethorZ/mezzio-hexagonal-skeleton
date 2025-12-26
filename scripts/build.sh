#!/bin/bash
# Build Script - Build production Docker images

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}🏗️  Building Production Image${NC}"
echo "=========================================="
echo ""

# Build production image
echo -e "${BLUE}📦 Building production Docker image...${NC}"
docker-compose -f docker-compose.prod.yml build

echo ""
echo -e "${GREEN}✅ Production image built successfully${NC}"
echo ""
echo "To start production environment:"
echo "  docker-compose -f docker-compose.prod.yml up -d"
echo ""

