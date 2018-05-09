I18N library for Erebot
=======================

This library was initially developped to provide I18N (internationalization)
features for the `Erebot <https://www.erebot.net/>`_ IRC bot.

It is aimed at working primarily with files generated using the gettext
suite of programs. It can load GNU Gettext PO and MO files, as well
as Solaris Gettext MO files.

The library's programming interface is pretty straightforward if you have
ever worked with Gettext. It is loosely based on that of the Python ``gettext``
module, works on PHP 7.0+ and does not require the ``Gettext`` PHP extension.


Installation
------------

To use this library in your project, you must already be using
the `Composer <https://getcomposer.org/>`_ dependency manager.

Simply add this library to your project dependencies:

..  sourcecode:: bash

    $ php /path/to/composer.phar require erebot/intl


Usage
-----

The simplest way to start working with this library is to use
the ``GettextFactory`` abstract class like so:

..  sourcecode:: php

    <?php

    // This is the name of the domain the translations will be taken from.
    $domain = 'messages';

    // This is the path to a directory containing a Gettext-compatible file hierarchy.
    $localedir = __DIR__ . DIRECTORY_SEPARATOR . 'i18n';

    // This is a list of languages to try and use, in order of descending preference.
    $languages = array('fr_FR');

    // Retrieve an instance of a translator.
    // This will first try to load a GNU Gettext PO file for the given domain/language,
    // then fall back to a GNU Gettext MO file, and then to a Solaris MO file.
    $translator = \Erebot\Intl\GettextFactory::translation($domain, $localedir, $languages);

    // Translate a single word, using the default context.
    // In French, this could be translated into "lunettes" (eyeglasses)
    echo $translator->gettext('glasses');

    // Translate the same word, using a specific context.
    // "_" is an alias/shorthand for the "gettext" method.
    // In French, this would be translated into "verres" (drinking glasses)
    echo $translator->gettext('glasses', 'drinks');

    // Translate a sentence using either the appropriate singular or plural form.
    // Please note that "ngettext" is called first to retrieve the proper translation
    // based on the number of lines in the file, then "printf" is used to replace
    // the "%d" format placeholder with that same value.
    echo printf($translator->ngettext('There is %d line in this file', 'There are %d lines in this file', __LINE__), __LINE__) . PHP_EOL;


License
-------

This library is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this library. If not, see <http://www.gnu.org/licenses/>.


.. vim: ts=4 et
