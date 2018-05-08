<?php

namespace Erebot\Intl\Translator;

use Erebot\Intl\TranslatorInterface;

abstract class AbstractGettext extends TranslatorInterface
{
    protected $filename;
    protected $locale;
    protected $domain;

    protected function __construct($filename, $locale, $domain)
    {
        $this->filename = $filename;
        $this->locale   = $locale;
        $this->domain   = $domain;

        $this->load($filename);
    }

    public static function translation($domain, $localedir = null, array $languages = array(), $fallback = false)
    {
        // We need to duplicate this here, so as to trim the path later on.
        if ($localedir === null) {
            $localedir = PHP_PREFIX . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'locale';
        }

        $files = static::find($domain, $localedir, $languages, true);

        $res = ($fallback && !count($files)) ? new NullTranslator : null;
        while (count($files)) {
            $file = array_pop($files);

            // Remove the $localedir + "/" prefix, then grab the locale's name.
            // ie. everything upto the next occurrence of DIRECTORY_SEPARATOR.
            list($locale, ) = explode(DIRECTORY_SEPARATOR, substr($file, strlen($localedir) + 1), 2);

            $tmp = new static($file, $locale, $domain);
            if ($res !== null) {
                $tmp->addFallback($res);
            }
            $res = $tmp;
        }

        if ($res === null) {
            throw new \Exception('No translation found');
        }

        return $res;
    }

    protected static function iterLanguages($languages)
    {
        foreach ($languages as $lang) {
            if (!is_string($lang)) {
                throw new \InvalidArgumentException('Invalid language');
            }

            if (!$lang) {
                continue;
            }

            $res = locale_parse($lang);
            if (!isset($res['language'])) {
                throw new \InvalidArgumentException('Invalid language');
            }

            if (isset($res['region'])) {
                yield locale_compose(array('language' => $res['language'], 'region' => $res['region']));
            }
            yield locale_compose(array('language' => $res['language']));
        }
    }

    public static function find($domain, $localedir = null, array $languages = array(), $all = false)
    {
        if ($localedir === null) {
            $localedir = PHP_PREFIX . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'locale';
        }

        if (!is_string($domain)) {
            throw new \InvalidArgumentException('Invalid domain name');
        }

        if (!is_string($localedir)) {
            throw new \InvalidArgumentException('Invalid locale directory');
        }

        // Map each class to its file extension, eg. "GettextGnuMo" => "mo".
        $ext = strtolower(substr(static::class, -2));
        $res = array();
        foreach (static::iterLanguages($languages) as $lang) {
            $file = $localedir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR .
                    'LC_MESSAGES' . DIRECTORY_SEPARATOR . $domain . '.' . $ext;
            if (file_exists($file)) {
                if (!$all) {
                    return $file;
                }
                $res[] = $file;
            }
        }
        if (!$all && !count($res)) {
            throw new \Exception('No translation found');
        }
        return $res;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    abstract protected function load($filename);
}
