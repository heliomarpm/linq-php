<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);

$lockFile = $baseDir . '/composer.lock';
if (is_file($lockFile)) {
    unlink($lockFile);
    echo "composer.lock removido com sucesso.\n";
}

$vendorDir = $baseDir . '/vendor';
if (is_dir($vendorDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($vendorDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }

    rmdir($vendorDir);
    echo "Diretório vendor/ removido com sucesso.\n";
}
