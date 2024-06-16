<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit88d0fcd314ed372d2bb6abe534b9213a
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'Ollama\\' => 7,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Ollama\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app/middleware',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'App\\Controller' => __DIR__ . '/../..' . '/app/Controller.php',
        'App\\Router' => __DIR__ . '/../..' . '/app/Router.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Ollama\\Ollama' => __DIR__ . '/../..' . '/app/middleware/Ollama.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit88d0fcd314ed372d2bb6abe534b9213a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit88d0fcd314ed372d2bb6abe534b9213a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit88d0fcd314ed372d2bb6abe534b9213a::$classMap;

        }, null, ClassLoader::class);
    }
}
