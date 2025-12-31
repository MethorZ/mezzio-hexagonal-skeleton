#!/bin/bash
# Initial Setup Script
# Run this once to set up the project for the first time

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}  Project Setup${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# Check if Docker is installed
echo "Checking prerequisites..."
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed${NC}"
    echo "Please install Docker Desktop from: https://www.docker.com/products/docker-desktop"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo -e "${RED}Docker Compose is not installed${NC}"
    echo "Please install Docker Compose from: https://docs.docker.com/compose/install/"
    exit 1
fi

# Check if Docker daemon is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Docker is not running${NC}"
    echo "Please start Docker Desktop and try again"
    exit 1
fi

echo -e "${GREEN}Docker and Docker Compose are installed and running${NC}"
echo ""

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo -e "${BLUE}Creating .env file...${NC}"
    cat > .env << 'EOF'
# ===========================================
# Environment Configuration
# ===========================================

# Application Environment
APP_ENV=development
APP_DEBUG=true

# Backend Port (external)
# Note: Each project needs a unique port if running multiple projects
BACKEND_PORT=8081

# Database Configuration
DB_PORT=33060
DB_ROOT_PASSWORD=root
DB_NAME=app_db
DB_USER=app_user
DB_PASSWORD=app_password

# Database Host (for application connection)
# Use 'database' when connecting from within Docker
DB_HOST=database

# JWT Authentication (if jwt-auth-middleware is installed)
# Generate a secure secret: openssl rand -base64 32
JWT_SECRET=change-this-to-a-secure-random-string
JWT_ALGORITHM=HS256
EOF
    echo -e "${GREEN}Created .env file${NC}"
else
    echo -e "${YELLOW}.env file already exists, skipping${NC}"
fi
echo ""

# Check if installer needs to run
INSTALLER_RAN=0
if [ -d "backend/src/SkeletonInstaller" ]; then
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  Interactive Package Installer${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    docker run --rm -it -v "$(pwd):/app" -w /app composer:latest run-script setup-installer

    # Delete composer.lock since composer.json was just modified
    # This forces a fresh lock file generation with the new packages
    rm -f composer.lock
    INSTALLER_RAN=1
    echo ""
fi

# Install backend dependencies
# Always install after installer runs (to get new packages), otherwise only if vendor missing
if [ $INSTALLER_RAN -eq 1 ] || [ ! -d "backend/vendor" ]; then
    echo -e "${BLUE}Installing selected packages...${NC}"

    # Always run composer from backend directory (unified approach for both architectures)
    docker run --rm -v "$(pwd)/backend:/app" -w /app composer:latest install --no-interaction --ignore-platform-reqs 2>&1 | grep -v "^$"

    echo -e "${GREEN}Packages installed${NC}"
    echo ""
fi

# Enable development mode
echo -e "${BLUE}Enabling development mode...${NC}"
docker run --rm -v "$(pwd)/backend:/app" -w /app composer:latest run-script development-enable --no-interaction 2>/dev/null || true
echo -e "${GREEN}Development mode configured${NC}"
echo ""

# Build Docker images
echo -e "${BLUE}Building Docker images (this may take a few minutes on first run)...${NC}"
docker-compose build
echo -e "${GREEN}Docker images built${NC}"
echo ""

# Start containers
echo -e "${BLUE}Starting Docker containers...${NC}"
docker-compose up -d
echo -e "${GREEN}Containers started${NC}"
echo ""

# Give containers time to initialize (especially on first run with fresh database)
echo -e "${BLUE}Waiting for containers to initialize (database + backend startup)...${NC}"
sleep 10
echo ""

# Verify setup completed successfully
echo -e "${BLUE}Verifying setup...${NC}"
echo ""

SETUP_FAILED=0

# Check 1: Containers are running
echo -n "  Checking containers... "
if docker-compose ps | grep -q "Up"; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗ Containers not running${NC}"
    SETUP_FAILED=1
fi

# Check 2: Wait for backend to be ready (up to 60 seconds on first run)
printf "  Waiting for backend... "
MAX_ATTEMPTS=60
ATTEMPT=0
BACKEND_READY=0
HTTP_STATUS=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    # Get HTTP status - use || true to prevent set -e from exiting on connection refused
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8081 2>/dev/null) || true

    # If empty or connection failed, set to 000
    if [ -z "$HTTP_STATUS" ] || [ "$HTTP_STATUS" = "0" ] || [ "$HTTP_STATUS" = "000" ]; then
        HTTP_STATUS="000"
    fi

    if [ "$HTTP_STATUS" = "200" ]; then
        BACKEND_READY=1
        break
    elif [ "$HTTP_STATUS" != "000" ] && [ "${HTTP_STATUS:0:1}" != "5" ]; then
        # Backend is responding with 4xx error (real error, not startup)
        BACKEND_READY=2
        break
    fi
    # For 000 (no connection) or 5xx (startup errors), keep waiting

    # Show progress indicator every 5 seconds
    MOD_RESULT=$((ATTEMPT % 5))
    if [ $MOD_RESULT -eq 0 ] && [ $ATTEMPT -gt 0 ]; then
        printf "."
    fi

    sleep 1
    ATTEMPT=$((ATTEMPT + 1))
done

echo ""  # New line after progress indicators
if [ $BACKEND_READY -eq 1 ]; then
    echo -e "    ${GREEN}✓ Backend ready${NC} (responded after ${ATTEMPT}s)"
elif [ $BACKEND_READY -eq 2 ]; then
    echo -e "    ${RED}✗ Backend responding with HTTP $HTTP_STATUS${NC}"
    echo ""
    echo -e "${YELLOW}Application error detected. Showing error details:${NC}"
    echo ""
    curl -s http://localhost:8081 2>/dev/null | head -30
    echo ""
    SETUP_FAILED=1
else
    echo -e "    ${RED}✗ Backend not responding after 60s${NC}"
    echo ""
    echo -e "${YELLOW}Checking container logs for errors:${NC}"
    docker-compose logs --tail=20 backend
    echo ""
    SETUP_FAILED=1
fi

# Check 3: No critical errors in logs
echo -n "  Checking for errors... "
if docker-compose logs backend 2>&1 | grep -qi "fatal\|critical"; then
    echo -e "${YELLOW}⚠ Warnings in logs (may be normal)${NC}"
else
    echo -e "${GREEN}✓${NC}"
fi

# Check 4: Verify vendor directory exists
echo -n "  Checking dependencies... "
if [ -d "backend/vendor" ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗ Dependencies not installed${NC}"
    SETUP_FAILED=1
fi

echo ""

# Clean up if all checks passed
if [ $SETUP_FAILED -eq 0 ]; then
    echo -e "${BLUE}Cleaning up setup files...${NC}"

    # Remove setup scripts from composer.json using docker (only if file exists)
    if [ -f "composer.json" ]; then
        docker run --rm -v "$(pwd):/app" -w /app composer:latest \
            php -r "\$json = json_decode(file_get_contents('composer.json'), true); \
                    unset(\$json['scripts']['post-create-project-cmd']); \
                    unset(\$json['scripts']['setup-installer']); \
                    file_put_contents('composer.json', json_encode(\$json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);" \
            2>/dev/null

        echo -e "   ${GREEN}✓${NC} Removed setup scripts from composer.json"
    fi

    # Remove this setup script
    rm -f setup.sh
    echo -e "   ${GREEN}✓${NC} Removed setup.sh"
    echo ""

    # Show success message
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}  🎉 Setup Complete! Your project is ready!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${BLUE}🌐 Your application is running:${NC}"
    echo -e "   ${GREEN}http://localhost:8081${NC}"
    echo ""
    echo -e "${BLUE}📝 Daily Development Commands:${NC}"
    echo -e "   Start services:    ${GREEN}make start${NC}"
    echo -e "   Stop services:     ${GREEN}make stop${NC}"
    echo -e "   View logs:         ${GREEN}make logs-f${NC}"
    echo -e "   Run tests:         ${GREEN}make test${NC}"
    echo -e "   Code quality:      ${GREEN}make quality${NC}"
    echo -e "   Backend shell:     ${GREEN}make shell${NC}"
    echo ""
    echo -e "${GREEN}Happy coding! 🚀${NC}"
    echo ""
else
    # Setup failed - keep files for debugging
    echo -e "${RED}=========================================${NC}"
    echo -e "${RED}  Setup Failed - See Errors Above${NC}"
    echo -e "${RED}=========================================${NC}"
    echo ""
    echo -e "${YELLOW}Setup files kept for debugging.${NC}"
    echo -e "${YELLOW}Check logs with: ${NC}${BLUE}make logs${NC}"
    echo ""
    echo -e "${YELLOW}You can retry setup after fixing issues:${NC}"
    echo -e "   ${BLUE}./setup.sh${NC}"
    echo ""
    exit 1
fi
