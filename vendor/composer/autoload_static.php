<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit87ec9a1add67e295c46dcec822c68a0c
{
    public static $files = array (
        '45a16669595eb3c0a9e2994e57fc3188' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v5p3.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'MatomoTracker' => __DIR__ . '/..' . '/matomo/matomo-php-tracker/MatomoTracker.php',
        'PiwikTracker' => __DIR__ . '/..' . '/matomo/matomo-php-tracker/PiwikTracker.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit87ec9a1add67e295c46dcec822c68a0c::$classMap;

        }, null, ClassLoader::class);
    }
}
