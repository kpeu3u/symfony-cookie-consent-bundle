<?php

// Configure a disposable CI checkout before resolving a Symfony version matrix.
$version = $argv[1] ?? '';
if (!in_array($version, ['7.4.*', '8.0.*', '8.1.*'], true)) {
    throw new InvalidArgumentException('Expected a supported Symfony test version.');
}
$doctrineVersion = $argv[2] ?? null;
if ($doctrineVersion !== null && !in_array($doctrineVersion, ['2.19.*', '3.3.*'], true)) {
    throw new InvalidArgumentException('Expected a supported DoctrineBundle test version.');
}
$migrationsVersion = $argv[3] ?? null;
if ($migrationsVersion !== null && !in_array($migrationsVersion, ['^3.3', '^4.0'], true)) {
    throw new InvalidArgumentException('Expected a supported DoctrineMigrationsBundle test version.');
}
$path = dirname(__DIR__, 2).'/composer.json';
$config = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
foreach (['require', 'require-dev'] as $section) {
    foreach ($config[$section] as $package => $constraint) {
        if (str_starts_with($package, 'symfony/') && (str_contains($constraint, '^7.4') || in_array($constraint, ['7.4.*', '8.0.*', '8.1.*'], true))) {
            $config[$section][$package] = $version;
        }
    }
}
$config['require-dev']['symfony/framework-bundle'] = $version;
if ($doctrineVersion !== null) {
    $config['require']['doctrine/doctrine-bundle'] = $doctrineVersion;
}
if ($migrationsVersion !== null) {
    $config['require']['doctrine/doctrine-migrations-bundle'] = $migrationsVersion;
}
unset($config['config']['platform']);
file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
