<?php

namespace Erebot\Intl\Translator;

use Erebot\Intl\Translation;

class GnuGettextMo extends AbstractGettext
{
    /* $catalog[$context][$msgid][$plural_forms],
     * where $msgid may contain both the singular & plural form
     * (separated with a NUL byte), and $plural_forms contains
     * a single value at index 0 for a singular-only message,
     * or several values (corresponding to the various plurals). */
    protected $catalog;
    protected $metadata;

    protected $charset;
    protected $nplurals;
    protected $plural;

    const NO_CONTEXT = "\x04";

    protected function __construct($filename, $locale, $domain)
    {
        parent::__construct($filename, $locale, $domain);

        if (isset($this->catalog[self::NO_CONTEXT][''][0])) {
            $this->parseMetadata();
        }

        if (isset($this->metadata['language']['value']) && strcasecmp($this->metadata['language']['value'], $locale)) {
            throw new \RuntimeException('Locale mismatch');
        }

        $this->charset  = 'UTF-8';
        $this->nplurals = 1;

        if (isset($this->metadata['content-type']['params']['charset'])) {
            $this->charset = $this->metadata['content-type']['params']['charset'];
        }

        if (isset($this->metadata['plural-forms']['params']['nplurals'])) {
            $this->nplurals = (int) $this->metadata['plural-forms']['params']['nplurals'];
            if ((string) $this->nplurals !== $this->metadata['plural-forms']['params']['nplurals']) {
                throw new \RuntimeException('Invalid metadata (invalid plurals count)');
            }
        }

        if (isset($this->metadata['plural-forms']['params']['plural'])) {
            $parser = new \Erebot\Intl\PluralParser($this->metadata['plural-forms']['params']['plural']);
            $this->plural = $parser->getEvaluator();
        } elseif ($this->nplurals === 1) {
            $this->plural = function ($n) {
                return 0;
            };
        } else {
            throw new \RuntimeException('Invalid metadata (missing plural expression)');
        }
    }

    protected function parseMetadata()
    {
        $metadata = rtrim($this->catalog[self::NO_CONTEXT][''][0], "\n");
        foreach (explode("\n", $metadata) as $line) {
            if ($line === '') {
                continue;
            }

            $colon = strpos($line, ':');
            if ($colon === false) {
                throw new \RuntimeException('Invalid metadata (missing value)');
            }

            $header = strtolower((string) substr($line, 0, $colon));
            if ($header === '') {
                throw new \RuntimeException('Invalid metadata (empty header)');
            }
            $this->metadata[$header] = array('params' => array());

            $line = (string) substr($line, $colon + 1);
            foreach (explode(";", $line) as $param) {
                $param = ltrim($param, " \t");
                if ($param === '') {
                    continue;
                }

                $eq = strpos($param, '=');
                if ($eq === false) {
                    if (isset($this->metadata[$header]['value'])) {
                        throw new \RuntimeException('Invalid metadata (value expected; got parameter)');
                    }
                    $this->metadata[$header]['value'] = $param;
                } else {
                    $name   = strtolower((string) substr($param, 0, $eq));
                    $value  = (string) substr($param, $eq + 1);

                    if ($name === '' || isset($this->metadata[$header]['params'][$name])) {
                        throw new \RuntimeException('Invalid metadata (invalid or duplicate parameter)');
                    }
                    $this->metadata[$header]['params'][$name] = $value;
                }
            }
        }
    }

    /**
     * See https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html#MO-Files
     * for more information about this file format.
     */
    protected function load($filename)
    {
        $this->catalog = array();

        $fp = fopen($filename, 'rb', false);
        if ($fp === false) {
            throw new \RuntimeException("Could not read $filename");
        }

        $data = fread($fp, 20);
        if (strlen($data) != 20) {
            throw new \RuntimeException('Invalid file');
        }

        $magic = substr($data, 0, 4);
        if ($magic === "\x95\x04\x12\xde") {
            $dword = 'N';
        } elseif ($magic === "\xde\x12\x04\x95") {
            $dword = 'V';
        } else {
            throw new \RuntimeException('Invalid file');
        }

        list(, $revision) = unpack("@4/${dword}", $data);

        if ($revision !== 0) {
            throw new \RuntimeException('Invalid file');
        }

        list(, $count, $origOffset, $transOffset) = unpack("@8/${dword}3", $data);

        fseek($fp, $origOffset);
        $origEntries = array();
        for ($i = 0; $i < $count; $i++) {
            $origEntries[] = unpack("${dword}2", fread($fp, 8));
        }

        fseek($fp, $transOffset);
        $transEntries = array();
        for ($i = 0; $i < $count; $i++) {
            $transEntries[] = unpack("${dword}2", fread($fp, 8));
        }

        for ($i = 0; $i < $count; $i++) {
            fseek($fp, $origEntries[$i][2]);
            $msgid = $origEntries[$i][1] > 0 ? fread($fp, $origEntries[$i][1]) : "";
            fseek($fp, $transEntries[$i][2]);
            $translations = $transEntries[$i][1] > 0 ? explode("\x00", fread($fp, $transEntries[$i][1])) : "";

            $ctx = self::NO_CONTEXT;
            if (($pos = strpos($msgid, self::NO_CONTEXT)) !== false) {
                $ctx = (string) substr($msgid, 0, $pos);
                $msgid = (string) substr($msgid, $pos + 1);
            }

            $this->catalog[$ctx][$msgid] = $translations;
        }
    }

    protected function lookup($singular, $plural, $n, $context): Translation
    {
        if ($context === null) {
            $context = self::NO_CONTEXT;
        }

        $msgid = $singular;
        if ($plural !== null) {
            $msgid     .= "\x00" . $plural;
            $evaluator  = $this->plural;
            $variant    = $evaluator($n);
        } else {
            $variant    = 0;
        }

        if (!isset($this->catalog[$context][$msgid][$variant])) {
            $res = $variant ? $plural : $singular;
        } else {
            $res = $this->catalog[$context][$msgid][$variant];
        }
        $res = iconv($this->charset, 'UTF-8', $res);
        if ($res === false) {
            throw new \RuntimeException('Conversion failed');
        }
        return new Translation($res, $context, $this);
    }
}
