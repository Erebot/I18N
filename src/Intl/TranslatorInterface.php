<?php

namespace Erebot\Intl;

use Erebot\Intl\Translation;

abstract class TranslatorInterface
{
    protected $fallback = null;

    public function addFallback(TranslatorInterface $fallback)
    {
        if ($this->fallback !== null) {
            $this->fallback->addFallback($fallback);
        } else {
            $this->fallback = $fallback;
        }
    }

    public function getFallback()
    {
        return $this->fallback;
    }

    public function _($msg, $context = null)
    {
        return $this->gettext($msg, $context);
    }

    public function gettext($msg, $context = null)
    {
        if (!is_string($msg)) {
            throw new \InvalidArgumentException();
        }

        $res = $this->lookup($msg, null, 1, $context);
        if ($res === null && $this->fallback !== null) {
            $res = $this->fallback->gettext($msg, $context);
        }
        if ($res === null) {
            $res = new Translation($msg, $context, $this);
        }
        return $res;
    }

    public function ngettext($singular, $plural, $n, $context = null)
    {
        if (!is_string($singular) || !is_string($plural) || !is_int($n)) {
            throw new \InvalidArgumentException();
        }

        $res = $this->lookup($singular, $plural, $n, $context);
        if ($res === null && $this->fallback !== null) {
            $res = $this->fallback->ngettext($singular, $plural, $n, $context);
        }
        if ($res === null) {
            $res = new Translation(($n == 1) ? $singular : $plural, $context, $this);
        }
        return $res;
    }

    abstract protected function lookup($singular, $plural, $n, $context): Translation;
    abstract public function getFilename();
    abstract public function getDomain();
    abstract public function getLocale();
}
