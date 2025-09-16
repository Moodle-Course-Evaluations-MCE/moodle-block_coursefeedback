<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Language manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\manager;

/**
 * Language manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class language_manager {

    /**
     * Get a list of all installed languages.
     * @return array
     */
    public static function get_languages() {
        return \get_string_manager()->get_list_of_translations();
    }

    /**
     * Fetch the given texts in a specific language.
     * @param array $requests Array of textids.
     * @param string $language Identifier for language.
     * @return array
     */
    public static function fetch_strings(array $requests, string $language) {
        global $DB;
        // TODO respect language.
        [$insql, $inparams] = $DB->get_in_or_equal(array_values($requests), SQL_PARAMS_NAMED);
        $inparams['lang'] = $language;
        $results = $DB->get_records_select_menu(
            'block_coursefeedback_texttranslation',
            "lang = :lang AND textid $insql",
            $inparams,
            '',
            'textid, text'
        );
        return $results;
    }

    /**
     * Fetch one text in a specific language.
     * @param int $textid
     * @param string $language
     */
    public static function fetch_string(int $textid, string $language) {
        global $DB;
        return $DB->get_field('block_coursefeedback_texttranslation', 'text', ['textid' => $textid, 'lang' => $language]);
    }

    /**
     * Update the string with the given textid in a given language to a new text.
     * @param int $textid
     * @param string $text
     * @param string $language
     */
    public static function update_string(int $textid, string $text, string $language) {
        global $DB;
        $DB->execute(
            'UPDATE {block_coursefeedback_texttranslation} SET text = :text WHERE textid = :textid AND lang = :language',
            ['text' => $text, 'textid' => $textid, 'language' => $language]
        );
    }

    /**
     * Create a new string in a specified language.
     * @param string $text
     * @param string $language
     * @param int $format
     * @return int the textid of the newly created string.
     */
    public static function create_string(string $text, string $language, $format = FORMAT_PLAIN) {
        global $DB;
        $textid = $DB->get_field_sql('SELECT max(id) + 1 FROM {block_coursefeedback_texttranslation}') ?: 1;
        $DB->insert_record('block_coursefeedback_texttranslation', [
            'textid' => $textid,
            'text' => $text,
            'format' => $format,
            'lang' => $language,
        ]);
        return $textid;
    }

    /**
     * Returns the default language for a surveypart.
     * @param int $surveypartid
     * @return string
     */
    public static function get_default_language_for_surveypart(int $surveypartid) {
        return 'de';
    }
}
