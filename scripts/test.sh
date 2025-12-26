#!/bin/bash
# Test Script - Run all quality checks and tests

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧪 Running Tests${NC}"
echo "=========================================="
echo ""

# Check if backend container is running
if ! docker-compose ps backend | grep -q "Up"; then
    echo -e "${RED}❌ Backend container is not running${NC}"
    echo "Start the development environment first: ./scripts/dev.sh"
    exit 1
fi

# Function to run command in backend container
run_in_backend() {
    docker-compose exec -T backend "$@"
}

# Code Style Check
echo -e "${BLUE}📝 Running Code Style Check (PHP_CodeSniffer)...${NC}"
if run_in_backend composer cs-check; then
    echo -e "${GREEN}✅ Code style check passed${NC}"
else
    echo -e "${RED}❌ Code style check failed${NC}"
    echo "Run 'docker-compose exec backend composer cs-fix' to auto-fix issues"
    exit 1
fi
echo ""

# Static Analysis
echo -e "${BLUE}🔍 Running Static Analysis (PHPStan level 9)...${NC}"
if run_in_backend composer analyze; then
    echo -e "${GREEN}✅ Static analysis passed${NC}"
else
    echo -e "${RED}❌ Static analysis failed${NC}"
    exit 1
fi
echo ""

# Unit Tests
echo -e "${BLUE}🎯 Running Unit Tests (PHPUnit)...${NC}"
if run_in_backend composer test; then
    echo -e "${GREEN}✅ All tests passed${NC}"
else
    echo -e "${RED}❌ Tests failed${NC}"
    exit 1
fi
echo ""

echo "=========================================="
echo -e "${GREEN}✅ All checks passed successfully!${NC}"
echo "=========================================="

