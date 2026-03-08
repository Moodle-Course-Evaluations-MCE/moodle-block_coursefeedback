<?php
// This file is part of the QuestionPy Moodle plugin - https://questionpy.org
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_coursefeedback\local;

/**
 * Utility function dealing with languages.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lang_utils {

    /**
     * Tries to find the best human-readable name for the given language code.
     *
     * This tries to find the name in the current language and falls back to English if that fails.
     *
     * @param string $lang_code
     * @return string
     */
    public static function get_language_label(string $lang_code): string {
        $stringman = get_string_manager();

        $name = null;
        if ($lang_code === current_language()) {
            $name = $stringman->get_string('thislanguage', 'langconfig', lang: $lang_code);
        }

        // First, we try to get the name of the target language in the current language.
        // To do this, we first need to get the ISO 639-2 code for the target language.
        $iso6392 = $stringman->get_string('iso6392', 'langconfig', lang: $lang_code);
        if ($iso6392 && !str_starts_with($iso6392, '[[')) {
            // And then look up the name in the ISO 639-2 component of the current language.
            $name = $stringman->get_string($iso6392, 'iso6392');
        }

        // If that failed, get the name of the target language in English.
        if (!$name || str_starts_with($name, '[[')) {
            $name = $stringman->get_string('thislanguageint', 'langconfig', lang: $lang_code);
        }

        return "$name ($lang_code)";
    }

    /**
     * Does {@see get_language_label()} for each language code in the given array.
     *
     * @param array $lang_codes List of language codes.
     * @return array Associative array of language codes to labels.
     */
    public static function get_language_labels(array $lang_codes): array {
        $labels = [];
        foreach ($lang_codes as $lang_code) {
            $labels[$lang_code] = self::get_language_label($lang_code);
        }
        return $labels;
    }
}
