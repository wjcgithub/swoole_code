<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9c23092af247b98b73a5f972b72dc1d1
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Swoole\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Swoole\\' => 
        array (
            0 => __DIR__ . '/..' . '/eaglewu/swoole-ide-helper/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9c23092af247b98b73a5f972b72dc1d1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9c23092af247b98b73a5f972b72dc1d1::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
