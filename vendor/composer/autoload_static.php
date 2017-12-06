<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1bc7b554025d7f61a37d981231320da3
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1bc7b554025d7f61a37d981231320da3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1bc7b554025d7f61a37d981231320da3::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
