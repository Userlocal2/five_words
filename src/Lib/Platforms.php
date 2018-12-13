<?php

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Log\Log;

/**
 * Class Platforms
 * @package ApiClient\Lib
 */
class Platforms
{
    /** @var ServerRequest */
    private static $platforms;
    private static $platformName = 'default';
    public static  $default      = 'default';

    /**
     * @var PlatformConfig
     */
    private static $platformConf;

    /**
     * @param string $cc
     *
     * @return string
     */
    public static function getPlatformByCC(string $cc) {
        if (null === self::$platforms) {
            self::$platforms = Configure::read('Platforms') ?? [];
        }

        /** @var string $platformName */
        foreach (self::$platforms as $platformName => $platformConf) {
            if (empty($platformConf['hosts']) || !is_array($platformConf['hosts'])) {
                continue;
            }
            foreach ($platformConf['hosts'] as $platformHost) {
                if ($cc === $platformName
                    || $cc === hash('md5', $platformHost)) {
                    return $platformName;
                }
            }
        }

        return self::$default;
    }

    /**
     * @param string $host
     *
     * @return string
     */
    public static function getPlatformByHost(string $host) {
        if (null === self::$platforms) {
            self::$platforms = Configure::read('Platforms') ?? [];
        }

        /** @var string $platformName */
        foreach (self::$platforms as $platformName => $platformConf) {
            if (empty($platformConf['hosts']) || !is_array($platformConf['hosts'])) {
                continue;
            }
            foreach ($platformConf['hosts'] as $platformHost) {
                if ($host === $platformHost) {
                    return $platformName;
                }
            }
        }

        return self::$default;
    }

    /**
     * @return string
     */
    public static function getPlatformByShell() {
        if (null === self::$platforms) {
            self::$platforms = Configure::read('Platforms') ?? [];
        }
        $argv = env('argv');

        // shell
        foreach ($argv as $arg) {
            $pos = strpos($arg, 'connect');
            if (false !== $pos) {
                $arg = trim($arg);
                $arg = trim($arg, '\''); // remove results of escapeshellarg()
                list(, $connectNameShell) = explode('=', $arg);

                /** @var string $platformName */
                foreach (self::$platforms as $platformName => $platformConf) {
                    if ($connectNameShell === $platformName) {
                        return $platformName;
                    }
                }
            }
        }

        return self::$default;
    }

    /**
     * @param $platformNameSet
     */
    public static function setPlatform($platformNameSet) {
        if (null === self::$platforms) {
            self::$platforms = Configure::read('Platforms') ?? [];
        }

        /** @var string $platformNameExist */
        foreach (self::$platforms as $platformNameExist => $platformConf) {
            if ($platformNameExist === $platformNameSet) {
                self::$platformName = $platformNameExist;
                self::$platformConf = $platformConf;

                return;
            }
        }

        trigger_error('Cannot set platform name. Platform name is incorrect ' . $platformNameSet);
    }

    public static function getPlatformName() {
        return static::$platformName;
    }

    /**
     * @return PlatformConfig
     */
    public static function getPlatformConfig(): PlatformConfig {

        if (!is_object(static::$platformConf)) {
            $obj = json_decode(json_encode(static::$platformConf));
            try {
                static::$platformConf = Convert::mapToObject($obj, new PlatformConfig());
            }
            catch (\JsonMapper_Exception $e) {
                Log::write('error', 'Error in platform config(reset to defaults) ->' . static::$platformName . '  |  ' . $e->getMessage());
                static::$platformConf = new PlatformConfig();
            }
        }

        return static::$platformConf;
    }
}
