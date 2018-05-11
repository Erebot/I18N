<?php

namespace Erebot\Intl\Translator;

use Erebot\Intl\TranslatorInterface;
use Erebot\Intl\Translation;

final class NullTranslator extends TranslatorInterface
{
    protected function lookup($singular, $plural, $n, $context): Translation
    {
        try {
            if ($this->fallback !== null) {
                return $this->fallback->lookup($singular, $plural, $n, $context);
            }
        } catch (Exception $e) {
        }

        return new Translation(($n == 1) ? $singular : $plural, $context, $this);
    }

    public function getFilename()
    {
        return null;
    }

    public function getDomain()
    {
        return null;
    }

    public function getLocale()
    {
        // When used with the intl extension (ICU),
        // this emulates a locale with neutral rules,
        // akin to POSIX's "C" locale or Windows' LCID 0.
        return "en_US_POSIX";
    }
}
