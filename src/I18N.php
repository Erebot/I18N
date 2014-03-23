<?php
/*
    This file is part of Erebot, a modular IRC bot written in PHP.

    Copyright © 2010 François Poirotte

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Erebot\I18N;

/**
 * \brief
 *      A class which provides translations for
 *      messages used by the core and modules.
 */
class I18N implements \Erebot\I18N\I18NInterface
{
    /// Expiration time of entries in the cache (in seconds).
    const EXPIRE_CACHE = 60;

    /// A cache for translation catalogs, with some additional metadata.
    static protected $cache = array();

    /// The actual locales used for i18n.
    protected $locales;

    /// The component to get translations from (a module name or "Erebot").
    protected $component;

    /**
     * Creates a new translator for messages.
     *
     * \param string $component
     *      The name of the component to use for translations.
     *      This should be set to the name of the module
     *      or "Erebot" for the core.
     */
    public function __construct($component)
    {
        $this->locales = array();
        $categories = array(
            self::LC_CTYPE,
            self::LC_NUMERIC,
            self::LC_TIME,
            self::LC_COLLATE,
            self::LC_MONETARY,
            self::LC_MESSAGES,
            self::LC_PAPER,
            self::LC_NAME,
            self::LC_ADDRESS,
            self::LC_TELEPHONE,
            self::LC_MEASUREMENT,
            self::LC_IDENTIFICATION,
        );
        foreach ($categories as $category)
            $this->locales[$category] = "en_US";
        $this->component = $component;
    }

    public static function nameToCategory($name)
    {
        $categories = array_flip(
            array(
                self::LC_CTYPE          => 'LC_CTYPE',
                self::LC_NUMERIC        => 'LC_NUMERIC',
                self::LC_TIME           => 'LC_TIME',
                self::LC_COLLATE        => 'LC_COLLATE',
                self::LC_MONETARY       => 'LC_MONETARY',
                self::LC_MESSAGES       => 'LC_MESSAGES',
                self::LC_PAPER          => 'LC_PAPER',
                self::LC_NAME           => 'LC_NAME',
                self::LC_ADDRESS        => 'LC_ADDRESS',
                self::LC_TELEPHONE      => 'LC_TELEPHONE',
                self::LC_MEASUREMENT    => 'LC_MEASUREMENT',
                self::LC_IDENTIFICATION => 'LC_IDENTIFICATION',
            )
        );
        if (!isset($categories[$name]))
            throw new \InvalidArgumentException('Invalid category name');
        return $categories[$name];
    }

    public static function categoryToName($category)
    {
        $categories = array(
            self::LC_CTYPE          => 'LC_CTYPE',
            self::LC_NUMERIC        => 'LC_NUMERIC',
            self::LC_TIME           => 'LC_TIME',
            self::LC_COLLATE        => 'LC_COLLATE',
            self::LC_MONETARY       => 'LC_MONETARY',
            self::LC_MESSAGES       => 'LC_MESSAGES',
            self::LC_PAPER          => 'LC_PAPER',
            self::LC_NAME           => 'LC_NAME',
            self::LC_ADDRESS        => 'LC_ADDRESS',
            self::LC_TELEPHONE      => 'LC_TELEPHONE',
            self::LC_MEASUREMENT    => 'LC_MEASUREMENT',
            self::LC_IDENTIFICATION => 'LC_IDENTIFICATION',
        );
        if (!isset($categories[$category]))
            throw new \InvalidArgumentException('Invalid category');
        return $categories[$category];
    }

    public function getLocale($category)
    {
        if (!isset($this->locales[$category]))
            throw new \InvalidArgumentException('Invalid category');
        return $this->locales[$category];
    }

    private function getBaseDir($component)
    {
        $reflector = new \ReflectionClass($component);
        $parts = explode(DIRECTORY_SEPARATOR, $reflector->getFileName());
        do {
            $last = array_pop($parts);
        } while ($last !== 'src' && count($parts));
        $parts[] = 'data';
        $parts[] = 'i18n';
        $base = implode(DIRECTORY_SEPARATOR, $parts);
        return $base;
    }

    public function setLocale($category, $candidates)
    {
        $categoryName = self::categoryToName($category);
        if (!is_array($candidates))
            $candidates = array($candidates);
        if (!count($candidates))
            throw new \InvalidArgumentException('Invalid locale');

        $base = $this->getBaseDir($this->component);
        $newLocale = NULL;
        foreach ($candidates as $candidate) {
            if (!is_string($candidate))
                throw new \InvalidArgumentException('Invalid locale');

            $locale = \Locale::parseLocale($candidate);
            if (!is_array($locale) || !isset($locale['language']))
                throw new \InvalidArgumentException('Invalid locale');

            // For anything else than LC_MESSAGES,
            // we take the first candidate as is.
            if ($categoryName != 'LC_MESSAGES')
                $newLocale = $candidate;

            if ($newLocale !== NULL)
                continue;

            $catalog = str_replace('\\', '_', ltrim($this->component, '\\'));
            if (isset($locale['region'])) {
                $normLocale = $locale['language'] . '_' . $locale['region'];
                $file = $base .
                        DIRECTORY_SEPARATOR . $normLocale .
                        DIRECTORY_SEPARATOR . $categoryName .
                        DIRECTORY_SEPARATOR . $catalog . '.mo';
                if (file_exists($file)) {
                    $newLocale = $normLocale;
                    continue;
                }
            }

            $file = $base .
                DIRECTORY_SEPARATOR . $locale['language'] .
                DIRECTORY_SEPARATOR . $categoryName .
                DIRECTORY_SEPARATOR . $catalog . '.mo';
                if (file_exists($file)) {
                    $newLocale = $locale['language'];
                    continue;
                }
        }

        if ($newLocale === NULL)
            $newLocale = 'en_US';
        $this->locales[$category] = $newLocale;
        return $newLocale;
    }

    /**
     * Returns the translation for the given message,
     * as contained in some translation catalog (MO file).
     *
     * \param string $file
     *      Path to the translation catalog to use.
     *
     * \param string $message
     *      The message to translate.
     *
     * \param string $mode
     *      Either "MO" or "PO", indicating whether
     *      the given file refers to a MO or PO catalog.
     *
     * \retval string
     *      The translation matching the given message.
     *
     * \retval NULL
     *      The message could not be found in the translation
     *      catalog.
     *
     * \note
     *      This method implements a caching strategy
     *      so that the translation catalog is not read
     *      again every time this method is called
     *      but only when the catalog actually changed.
     */
    protected function get_translation($component, $message)
    {
        $time = time();
        $locale = $this->locales[self::LC_MESSAGES];
        if (!isset(self::$cache[$component][$locale]) ||
            $time > (self::$cache[$component][$locale]['added'] +
                     self::EXPIRE_CACHE)) {

            if (isset(self::$cache[$component][$locale]['file'])) {
                $file = self::$cache[$component][$locale]['file'];
            }
            else {
                try {
                    $file = $this->getBaseDir($component);
                }
                catch (Exception $e) {
                    return NULL;
                }

                $catalog = str_replace('\\', '_', ltrim($component, '\\'));
                $file .=    DIRECTORY_SEPARATOR . $locale .
                            DIRECTORY_SEPARATOR . 'LC_MESSAGES' .
                            DIRECTORY_SEPARATOR . $catalog . '.mo';

                if (!file_exists($file)) {
                    $file = substr($file, 0, -3) . '.po';
                }

                if (!file_exists($file)) {
                    return NULL;
                }
            }

            /**
             * FIXME: filemtime() raises a warning if the given file
             * could not be stat'd (such as when is does not exist).
             * An error_reporting level of E_ALL & ~E_DEPRECATED
             * would otherwise be fine for File_Gettext.
             */
            $oldErrorReporting = error_reporting(E_ERROR);

            if (version_compare(PHP_VERSION, '5.3.0', '>='))
                clearstatcache(FALSE, $file);
            else
                clearstatcache();

            $mtime = FALSE;
            if ($file !== FALSE) {
                $mtime = filemtime($file);
            }

            if ($mtime === FALSE) {
                // We also cache failures to avoid
                // harassing the CPU too much.
                self::$cache[$component][$locale] = array(
                    'mtime'     => $time,
                    'string'    => array(),
                    'added'     => $time,
                    'file'      => FALSE,
                );
            }
            else if (!isset(self::$cache[$component][$locale]) ||
                $mtime !== self::$cache[$component][$locale]['mtime']) {
                $parser = \File_Gettext::factory(substr($file, -2), $file);
                $parser->load();
                self::$cache[$component][$locale] = array(
                    'mtime'     => $mtime,
                    'strings'   => $parser->strings,
                    'added'     => $time,
                    'file'      => $file,
                );
            }
            error_reporting($oldErrorReporting);
        }

        if (isset(self::$cache[$component][$locale]['strings'][$message]))
            return self::$cache[$component][$locale]['strings'][$message];
        return NULL;
    }

    /**
     * Low-level translation method.
     *
     * \param string $message
     *      The message to translate.
     *
     * \param string $component
     *      The name of the component this translation
     *      belongs to, such as "Erebot" for core messages
     *      or "Erebot_Module_ABC" for messages belonging
     *      to the module named "Erebot_Module_ABC".
     *
     * \retval string
     *      Either the translation for the given message
     *      is returned, or the original message if none
     *      could be found.
     */
    protected function real_gettext($message, $component)
    {
        $translation = $this->get_translation(
            $component,
            $message
        );
        return ($translation === NULL) ? $message : $translation;
    }

    public function gettext($message)
    {
        return $this->real_gettext($message, $this->component);
    }

    public function _($message)
    {
        return $this->real_gettext($message, $this->component);
    }

    /**
     * Clears the cache used for translation catalogs.
     *
     * \retval
     *      This method does not return any value.
     */
    public static function clearCache()
    {
        self::$cache = array();
    }
}
