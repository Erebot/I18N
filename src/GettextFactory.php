<?php

namespace Erebot\Intl;

abstract class GettextFactory
{
    public static function translation($domain, $localedir = null, array $languages = array(), $fallback = false)
    {
        $classes = array(
            '\\Erebot\\Intl\\Translator\\GnuGettextPo',
            '\\Erebot\\Intl\\Translator\\GnuGettextMo',
            '\\Erebot\\Intl\\Translator\\SolarisGettextMo',
        );

        foreach ($classes as $cls) {
            try {
                return $cls::translation($domain, $localedir, $languages);
            } catch (\Exception $e) {
            }
        }

        if (!$fallback) {
            throw new \Exception('No translation found');
        }

        return new \Erebot\Intl\Translator\NullTranslator;
    }
}
