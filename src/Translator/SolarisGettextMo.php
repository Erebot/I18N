<?php

namespace Erebot\Intl\Translator;

use Erebot\Intl\Translation;

class SolarisGettextMo extends AbstractGettext
{
    /* $catalog[$context][$msgid][$plural_forms],
     * where $msgid may contain both the singular & plural form
     * (separated with a NUL byte), and $plural_forms contains
     * a single value at index 0 for a singular-only message,
     * or several values (corresponding to the various plurals). */
    protected $catalog;
    protected $nplurals;
    protected $plural;

    const NO_CONTEXT = "\x04";

    /**
     * See https://github.com/nxmirrors/onnv/blob/master/usr/src/cmd/msgfmt/msgfmt.c
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

        // Perform a basic sanity check & detect endianness.
        list(, $middleN, $countN) = unpack("N2", $data);
        list(, $middleV, $countV) = unpack("V2", $data);
        if ($middleN === (int) ($countN - 1) / 2) {
            $count = $countN;
            $dword = "N";
        } elseif ($middleV === (int) ($countV - 1) / 2) {
            $count = $countV;
            $dword = "V";
        } else {
            throw new \RuntimeException('Invalid file');
        }

        list(, $msgidSize, $msgstrSize, $msgStructSize) = unpack("@8/${dword}*", $data);

        /* We keep the whole catalog in memory, so we do not need
           to deal with the msg_struct binary search tree. */
        if (fseek($fp, $msgStructSize, SEEK_CUR) !== 0) {
            throw new \RuntimeException('Invalid file');
        }

        $msgid = array();
        if ($msgidSize > 0) {
            $data = fread($fp, $msgidSize);
            if (strlen($data) !== $msgidSize) {
                throw new \RuntimeException('Invalid file');
            }
            $msgid = explode("\x00", $data);
            array_pop($msgid); // Remove the last (empty entry).
        }

        $msgstr = array();
        if ($msgstrSize > 0) {
            $data = fread($fp, $msgstrSize);
            if (strlen($data) !== $msgstrSize) {
                throw new \RuntimeException('Invalid file');
            }
            $msgstr = explode("\x00", $data);
            array_pop($msgstr); // Remove the last (empty entry).
        }

        if (count($msgid) !== count($msgstr) || count($msgid) !== $count) {
            throw new \RuntimeException('Invalid file');
        }

        $this->catalog = array_combine($msgid, $msgstr);
    }

    protected function lookup($singular, $plural, $n, $context): Translation
    {
        if ($plural !== null || $context !== null) {
            // Solaris MO files do not support plurals/contexts.
            throw \InvalidArgumentException();
        }

        $res = isset($this->catalog[$singular]) ? $this->catalog[$singular] : $singular;
        return new Translation($res, $context, $this);
    }
}
