<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit45ba53129cc7023b37f00fe37c0eb15c
{
    public static $prefixLengthsPsr4 = array (
        'I' => 
        array (
            'IU\\REDCapETL\\' => 13,
            'IU\\PHPCap\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'IU\\REDCapETL\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'IU\\PHPCap\\' => 
        array (
            0 => __DIR__ . '/..' . '/iu-redcap/phpcap/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit45ba53129cc7023b37f00fe37c0eb15c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit45ba53129cc7023b37f00fe37c0eb15c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit45ba53129cc7023b37f00fe37c0eb15c::$classMap;

        }, null, ClassLoader::class);
    }
}
