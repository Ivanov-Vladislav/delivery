<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb44abc0c21177d632bf017babe476f08
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'DigitalStars\\SimpleVK\\' => 22,
            'DigitalStars\\DataBase\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'DigitalStars\\SimpleVK\\' => 
        array (
            0 => __DIR__ . '/..' . '/digitalstars/simplevk/src',
        ),
        'DigitalStars\\DataBase\\' => 
        array (
            0 => __DIR__ . '/..' . '/digitalstars/database/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb44abc0c21177d632bf017babe476f08::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb44abc0c21177d632bf017babe476f08::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb44abc0c21177d632bf017babe476f08::$classMap;

        }, null, ClassLoader::class);
    }
}