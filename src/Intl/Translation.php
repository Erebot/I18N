<?php

namespace Erebot\Intl;

class Translation
{
    protected $value;
    protected $domain;
    protected $context;
    protected $locale;
    protected $filename;

    public function __construct($value, $context, TranslatorInterface $translator)
    {
        $this->value    = $value;
        $this->domain   = $translator->getDomain();
        $this->context  = $context;
        $this->locale   = $translator->getLocale();
        $this->filename = $translator->getFilename();
    }

    public function __toString()
    {
        return $this->value;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getFilename()
    {
        return $this->filename;
    }
}
