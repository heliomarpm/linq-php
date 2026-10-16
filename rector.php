<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/benchmarks',
        __DIR__ . '/demo',
        __DIR__ . '/api',
    ])
    ->withRootFiles()   //Inclui os arquivos PHP soltos na raiz (index.php, login.php, etc.)
    // ->withSkip([
    //     __DIR__ . '/vendor',
    //     __DIR__ . '/tests',
    // ])
    // Modernização para a sua versão do PHP (PHP 8.2)
    ->withPhpSets(php82: true)

    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withTypeCoverageLevel(0)
    // ->withDeadCodeLevel(0)
    // ->withCodeQualityLevel(0);
    ->withPreparedSets(
        deadCode: true,      // Remove variáveis não usadas e código inalcançável
        codeQuality: true,   // Simplifica condições redundantes e ifs aninhados
        typeDeclarations: false // Mantenha false no início em código procedural para evitar tipagens inesperadas
    );
