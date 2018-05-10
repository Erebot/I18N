<?php

namespace Erebot\Intl\Translator;

class GnuGettextPo extends GnuGettextMo
{
    /**
     * See https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html#PO-Files
     * for more information about this file format.
     */
    protected function load($filename)
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new \InvalidArgumentException('Invalid file');
        }

        $entry      = self::newEntry();
        $lastKw     = null;
        $lastIndex  = null;

        foreach ($lines as $line) {
            if ($line[0] === '#') {
                $this->maybePushEntry($entry);
                if (strlen($line) === 1) {
                    continue;
                }
                switch ($line[1]) {
                    case ' ':   # Translator comment
                        break;
                    case '.':   # Extracted comment
                        break;
                    case ':':   # File/line reference
                        break;
                    case ',':   # Flags
                        break;
                    case "|":   # Previous information
                        # The initial marker is followed by a keyword
                        # (either "msgid", "msgctxt" or "msgid_plural"),
                        # detailing what the previous information was.
                        break;
                }
                continue;
            }

            $line   = ltrim($line, " \t");
            $len    = strlen($line);

            // Reset keyword index on new entry
            if (!count($entry['msgstr'])) {
                $lastIndex = null;
            }

            // Look for continuation strings.
            if (strlen($line) >= 2 && $line[0] === '"' && $line[$len-1] === '"') {
                // We found a string, but it has no predecessor.
                if ($lastKw === null) {
                    throw new \InvalidArgumentException();
                }

                $line   = stripcslashes((string) substr($line, 1, -1));

                if ($lastKw === 'msgstr') {
                    $index = $lastIndex === null ? 0 : $lastIndex;
                } else {
                    $index = null;
                }

                if ($index === null) {
                    if (is_array($entry[$lastKw])) {
                        throw new \InvalidArgumentException();
                    } else {
                        $entry[$lastKw] .= $line;
                    }
                } else {
                    if (!is_array($entry[$lastKw])) {
                        throw new \InvalidArgumentException();
                    } else {
                        $entry[$lastKw][$index] .= $line;
                    }
                }
                continue;
            }

            $kwLen  = strcspn($line, " \t");
            $kw     = (string) substr($line, 0, $kwLen);
            $index  = null;
            $line   = ltrim((string) substr($line, $kwLen), " \t");

            $brkOpen    = strpos($kw, '[');
            $brkClose   = strpos($kw, ']');
            if ($brkOpen !== false || $brkClose !== false) {
                if ($brkOpen === false || $brkClose === false || $brkClose < $brkOpen) {
                    throw new \InvalidArgumentException();
                }

                $nlen = strspn($kw, '1234567890', $brkOpen + 1, $brkClose - $brkOpen - 1);
                if ($nlen === 0 || $nlen !== $brkClose - $brkOpen - 1) {
                    throw new \InvalidArgumentException();
                }

                $index  = (int) substr($kw, $brkOpen + 1, $nlen);
                $kw     = (string) substr($kw, 0, $brkOpen);
            }

            if (strlen($line) < 2 || $line[0] !== '"' || $line[strlen($line)-1] !== '"') {
                throw new \InvalidArgumentException();
            }
            $line = stripcslashes((string) substr($line, 1, -1));

            switch ($kw) {
                case "msgstr":
                    if ($index === null) {
                        if (isset($entry[$kw][0])) {
                            throw new \InvalidArgumentException();
                        }
                        $entry[$kw][0] = $line;
                    } else {
                        if (array_key_exists($index, $entry[$kw])) {
                            throw new \InvalidArgumentException();
                        }
                        $entry[$kw][$index] = $line;
                        $lastIndex = $index;
                    }
                    break;

                case "msgctxt":
                    // Intentional fall-through
                case "msgid":
                    $this->maybePushEntry($entry);
                    // Intentional fall-through
                case 'msgid_plural':
                    if ($index !== null || isset($entry[$kw])) {
                        throw new \InvalidArgumentException();
                    }
                    $entry[$kw] = $line;
                    break;

                default:
                    throw new \InvalidArgumentException();
            }
            $lastKw = $kw;
        }

        // Push the last entry.
        $this->maybePushEntry($entry);
    }

    protected static function newEntry()
    {
        return array(
            'msgctxt'       => null,
            'msgid'         => null,
            'msgid_plural'  => null,
            'msgstr'        => array(),
        );
    }

    public function maybePushEntry(&$entry)
    {
        if (isset($entry['msgid']) && count($entry['msgstr'])) {
            $ctx    = isset($entry['msgctxt']) ? $entry['msgctxt'] : self::NO_CONTEXT;
            $msgid  = $entry['msgid'];
            if (isset($entry['msgid_plural'])) {
                $msgid .= "\x00" . $entry['msgid_plural'];
            }
            if (isset($this->catalog[$msgid])) {
                throw new \Exception();
            }
            $this->catalog[$ctx][$msgid] = $entry['msgstr'];
            $entry  = self::newEntry();
        }
    }
}
