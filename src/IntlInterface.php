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

namespace Erebot;

/**
 * \brief
 *      Interface to provide internationalization.
 *
 * This interface provides the necessary methods
 * to provide internationalization of the messages
 * the bot emits through logging or exceptions.
 */
interface IntlInterface
{
    /// Character classification and case conversion.
    const LC_CTYPE          = 0;

    /// Non-monetary numeric formats.
    const LC_NUMERIC        = 1;

    /// Date and time formats.
    const LC_TIME           = 2;

    /// Collation order.
    const LC_COLLATE        = 3;

    /// Monetary formats.
    const LC_MONETARY       = 4;

    /**
     * Formats of informative and diagnostic messages
     * and interfactive responses.
     */
    const LC_MESSAGES       = 5;

    /// Overrides the value for all the other LC_* constants.
    const LC_ALL            = 6;

    /// Paper size.
    const LC_PAPER          = 7;

    /// Name formats.
    const LC_NAME           = 8;

    /// Address formats and location information.
    const LC_ADDRESS        = 9;

    /// Telephone number formats.
    const LC_TELEPHONE      = 10;

    /// Measurement units (Metric or Other).
    const LC_MEASUREMENT    = 11;

    /// Metadata about the locale information.
    const LC_IDENTIFICATION = 12;

    /**
     * Returns the value of the category matching the given name.
     *
     * \param string $name
     *      A category name, such as "LC_MESSAGES".
     *
     * \retval opaque
     *      The corresponding category, returned as one of
     *      the constants in ::Erebot::IntlInterface,
     *      eg. ::Erebot::IntlInterface::LC_MESSAGES.
     */
    public static function nameToCategory($name);

    /**
     * Returns the name associated with a given category.
     *
     * \param opaque $category
     *      One of the constants defined in ::Erebot::IntlInterface,
     *      eg. ::Erebot::IntlInterface::LC_MESSAGES.
     *
     * \retval string
     *      The name of that category (eg. "LC_MESSAGES").
     */
    public static function categoryToName($category);

    /**
     * Returns the target locale of this translator
     * in canonical form for a given catergory.
     *
     * \param opaque $category
     *      One of the constants defined in ::Erebot::IntlInterface
     *      indicating the category we're interested in querying.
     *      For the most basic usage, you should pass
     *      ::Erebot::IntlInterface::LC_MESSAGES as the category.
     *
     * \retval string
     *      The canonical form of the target locale
     *      for this translator.
     */
    public function getLocale($category);

    /**
     * Sets the target locale of this translator
     * for a given catergory.
     *
     * \param opaque $category
     *      One of the constants defined in ::Erebot::IntlInterface
     *      indicating the category we're interested in setting.
     *      For the most basic usage, you should pass
     *      ::Erebot::IntlInterface::LC_MESSAGES as the category.
     *
     * \param array $candidates
     *      Array of locales that can be used.
     *      The candidate locales will be reviewed in order
     *      and the first one to match a locale available
     *      to the bot will be used as the effective locale.
     *
     * \retval string
     *      The locale effectively in use after this method
     *      has been called, in its canonical form.
     *
     * \note
     *      The bot assumes the "en_US" locale is always available.
     *      If none of the given candidates matches a locale
     *      available to the bot, "en_US" will automatically
     *      be selected as the locale to use.
     */
    public function setLocale($category, $candidates);

    /**
     * Translates the given message using the translations
     * for the current component.
     *
     * \param string $message
     *      The original message to translate, in US English.
     *
     * \retval string
     *      The message, translated into the selected locale.
     */
    public function gettext($message);

    /**
     * \copydoc ::Erebot::IntlInterface::gettext
     *
     * \note
     *      This method is an alias for
     *      ::Erebot::IntlInterface::gettext
     */
    public function _($message);
}
