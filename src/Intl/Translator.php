<?php

namespace Erebot\Intl;

use Erebot\Intl\TranslatorInterface;
use Erebot\Intl\Translation;
use Erebot\Intl\Translator\NullTranslator;

class Translator extends TranslatorInterface implements \ArrayAccess
{
    protected $domains;
    protected $domainFallback;
    protected $defaultDomain;

    protected $localedir;

    public function __construct($fallback = false)
    {
        $this->domainFallback   = $fallback;
        $this->defaultDomain    = null;
    }

    public function setDefaultDomain($domain)
    {
        if ($domain !== null && !isset($this->domains[$domain])) {
            throw new \InvalidArgumentException('Invalid domain');
        }
        $this->defaultDomain = $domain;
    }

    public function getDefaultTranslator()
    {
        try {
            return $this[$this->defaultDomain];
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('No default translator has been defined yet');
        }
    }

    protected function lookup($singular, $plural, $n, $context): Translation
    {
        throw new \RuntimeException('Operation not supported');
    }

    public function addFallback(TranslatorInterface $fallback)
    {
        return $this->getDefaultTranslator()->addFallback($fallback);
    }

    public function gettext($msg, $context = null)
    {
        return $this->getDefaultTranslator()->gettext($msg, $context);
    }

    public function ngettext($singular, $plural, $n, $context = null)
    {
        return $this->getDefaultTranslator()->ngettext($singular, $plural, $n, $context);
    }

    public function dgettext($domain, $msg, $context = null)
    {
        return $this[$domain]->gettext($msg, $context);
    }

    public function dngettext($domain, $singular, $plural, $n, $context = null)
    {
        return $this[$domain]->ngettext($singular, $plural, $n, $context);
    }

    public function getFilename()
    {
        return $this->getDefaultTranslator()->getFilename();
    }

    public function getDomain()
    {
        return $this->getDefaultTranslator()->getDomain();
    }

    public function getLocale()
    {
        return $this->getDefaultTranslator()->getLocale();
    }

    public function offsetGet($domain)
    {
        if (!isset($this->domains[$domain])) {
            if ($this->domainFallback) {
                return new NullTranslator;
            }
            throw new \InvalidArgumentException('Invalid domain');
        }

        return $this->domains[$domain];
    }

    public function offsetSet($domain, $translator)
    {
        if (!($translator instanceof TranslatorInterface)) {
            throw new \InvalidArgumentException('Invalid translator');
        }

        $realDomain = $translator->getDomain();
        if ($domain !== null && $domain !== $realDomain) {
            throw new \InvalidArgumentException('Invalid domain');
        }

        $this->domains[$realDomain] = $translator;
    }

    public function offsetExists($domain)
    {
        return isset($this->domains[$domain]);
    }

    public function offsetUnset($domain)
    {
        unset($this->domains[$domain]);
        if ($this->defaultDomain === $domain) {
            $this->defaultDomain = null;
        }
    }
}
