<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit433c99e3d1b50e86b12f651c2c1522cb
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Crontrol\\Event\\Table' => __DIR__ . '/../..' . '/src/event-list-table.php',
        'Crontrol\\Request' => __DIR__ . '/../..' . '/src/request.php',
        'Crontrol\\Schedule_List_Table' => __DIR__ . '/../..' . '/src/schedule-list-table.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit433c99e3d1b50e86b12f651c2c1522cb::$classMap;

        }, null, ClassLoader::class);
    }
}
