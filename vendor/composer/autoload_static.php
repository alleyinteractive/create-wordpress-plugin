<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7a3dc386ef7feaff21857f0a5166b3a6
{
    public static $files = array (
        '22177d82d05723dff5b1903f4496520e' => __DIR__ . '/..' . '/alleyinteractive/wordpress-autoloader/src/class-autoloader.php',
        'd0b4d9ff2237dcc1a532ae9d039c0c2c' => __DIR__ . '/..' . '/alleyinteractive/composer-wordpress-autoloader/src/autoload.php',
    );

    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'ComposerWordPressAutoloader\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ComposerWordPressAutoloader\\' => 
        array (
            0 => __DIR__ . '/..' . '/alleyinteractive/composer-wordpress-autoloader/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7a3dc386ef7feaff21857f0a5166b3a6::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7a3dc386ef7feaff21857f0a5166b3a6::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7a3dc386ef7feaff21857f0a5166b3a6::$classMap;

        }, null, ClassLoader::class);
    }
}
