#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Clear configuration cache
 *
 * Removes the merged config cache file so that configuration changes take effect.
 */

chdir(dirname(__DIR__));

$cacheFile = 'data/cache/config-cache.php';

if (file_exists($cacheFile)) {
    if (unlink($cacheFile)) {
        printf("✅ Configuration cache cleared: %s\n", $cacheFile);
    } else {
        fprintf(STDERR, "❌ Failed to clear configuration cache: %s\n", $cacheFile);
        exit(1);
    }
} else {
    printf("ℹ️  Configuration cache does not exist: %s\n", $cacheFile);
}

exit(0);

