#!/bin/bash
# Development Environment Startup Script
# Starts the development environment with Docker
# Note: Run setup.sh first if this is your first time setting up the project

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Development Environment${NC}"
echo "=========================================="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running${NC}"
    echo "Please start Docker Desktop and try again"
    exit 1
fi

# Check if setup has been run
if [ ! -f .env ]; then
    echo -e "${YELLOW}.env file not found${NC}"
    echo -e "${YELLOW}It looks like you haven't run setup yet.${NC}"
    echo ""
    echo "Please run setup first:"
    echo -e "  ${GREEN}./setup.sh${NC}"
    echo ""
    exit 1
fi

if [ ! -d "backend/vendor" ]; then
    echo -e "${YELLOW}Backend dependencies not installed${NC}"
    echo -e "${YELLOW}It looks like you haven't run setup yet.${NC}"
    echo ""
    echo "Please run setup first:"
    echo -e "  ${GREEN}./setup.sh${NC}"
    echo ""
    exit 1
fi

# Stop any running containers
echo -e "${BLUE}Stopping any existing containers...${NC}"
docker-compose down

# Start containers
echo -e "${BLUE}Starting Docker containers...${NC}"
docker-compose up -d

# Wait for backend to be ready
echo -e "${YELLOW}Waiting for backend to be ready...${NC}"
sleep 3

# Show status
echo ""
echo -e "${GREEN}Development environment is ready!${NC}"
echo ""
echo "=========================================="
echo -e "${BLUE}Access URLs:${NC}"
echo "   Application:   http://localhost:8081"
echo "   Health Check:  http://localhost:8081/health"
echo ""
echo -e "${BLUE}Useful Commands:${NC}"
echo "   View logs:         docker-compose logs -f"
echo "   Stop containers:   docker-compose down"
echo "   Run tests:         ./scripts/test.sh"
echo "   Backend shell:     docker-compose exec backend bash"
echo ""
echo "=========================================="
