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

require(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Foo.php');

class IntlTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->translators = array();
        $locales = array(
            'en_US',
            'fr_FR',
        );
        foreach ($locales as $locale) {
            $this->translators[$locale] = new \Erebot\Intl("Foo");
            $this->translators[$locale]->setLocale(
                \Erebot\IntlInterface::LC_MESSAGES,
                $locale
            );
        }
    }

    /**
     * @covers \Erebot\Intl
     */
    public function testGetLocale()
    {
        foreach ($this->translators as $locale => $translator)
            $this->assertEquals(
                $locale,
                $translator->getLocale(\Erebot\IntlInterface::LC_MESSAGES)
            );
    }

    /**
     * @covers \Erebot\Intl
     */
    public function testTranslation()
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
}

