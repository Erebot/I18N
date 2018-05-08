<?php
/*
    This file is part of Erebot.

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

if (!class_exists('PHPUnit_Framework_TestCase')) {
    class_alias('\\PHPUnit\\Framework\\TestCase', 'PHPUnit_Framework_TestCase');
}

class GnuGettextPoTest extends PHPUnit_Framework_TestCase
{
    protected $factory = '\\Erebot\\Intl\\Translator\\GnuGettextPo';

    public function setUp()
    {
        $this->translators = array();
        $locales = array(
            'en_US',
            'fr_FR',
        );
        foreach ($locales as $locale) {
            $cls = $this->factory;
            $this->translators[$locale] = $cls::translation(
                "Foo",
                __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'i18n',
                array($locale),
                true
            );
        }
    }

    public function testGetLocale()
    {
        // A translator's getLocale() method returns
        // the locale associated with that translator.
        $this->assertEquals(
            'fr_FR',
            $this->translators['fr_FR']->getLocale()
        );

        // The fallback translator's getLocale() method
        // always returns "C" (a language-neutral locale).
        $this->assertEquals(
            'C',
            $this->translators['en_US']->getLocale()
        );
    }

    public function testBasicTranslations()
    {
        $message = "This is a test";
        $translations = array(
            'en_US' =>  $message,
            'fr_FR' =>  "Ceci est un test",
        );
        foreach ($translations as $locale => $translation)
            $this->assertEquals($translation,
                $this->translators[$locale]->gettext($message));
    }

    public function testContextBasedTranslation()
    {
        $message = "this translation is context-dependent";
        $translations = array(
            'en_US' =>  $message,
            'fr_FR' =>  "cette traduction dépend du contexte (custom context)",
        );
        foreach ($translations as $locale => $translation)
            $this->assertEquals($translation,
                $this->translators[$locale]->gettext($message, 'context'));

        $translations = array(
            'en_US' =>  $message,
            'fr_FR' =>  "cette traduction dépend du contexte (default context)",
        );
        foreach ($translations as $locale => $translation)
            $this->assertEquals($translation,
                $this->translators[$locale]->gettext($message));
    }

    public function testMultilineTranslation()
    {
        $message = "A multiline\ntext";
        $translations = array(
            'en_US' =>  $message,
            'fr_FR' =>  "Un texte\nmulti-lignes",
        );
        foreach ($translations as $locale => $translation)
            $this->assertEquals($translation,
                $this->translators[$locale]->gettext($message));
    }

    public function testPlurals()
    {
        $messages = array("found %d fatal error", "found %d fatal errors");
        $translations = array(
            'en_US' =>  $messages,
            'fr_FR' =>  array("%d erreur fatale trouvée", "%d erreurs fatales trouvées"),
        );
        foreach ($translations as $locale => $translation)
            list($singular, $plural) = $translation;
            $this->assertEquals($singular,
                $this->translators[$locale]->ngettext($messages[0], $messages[1], 1));
            $this->assertEquals($plural,
                $this->translators[$locale]->ngettext($messages[0], $messages[1], 42));
    }
}

class GnuGettextMoTest extends GnuGettextPoTest
{
    protected $factory = '\\Erebot\\Intl\\Translator\\GnuGettextMo';
}
