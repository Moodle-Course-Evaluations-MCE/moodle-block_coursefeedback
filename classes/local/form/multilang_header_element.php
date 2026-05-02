<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_coursefeedback\local\form;

use block_coursefeedback\local\lang_utils;
use MoodleQuickForm_group;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/group.php');

// Pre-existing conditions...
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

/**
 * A group element showing column headers for {@see multilang_input_element}s that should be added below.
 *
 * @see multilang_header_element
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class multilang_header_element extends MoodleQuickForm_group {

    /**
     * Initializes a new instance.
     *
     * @param string $name
     * @param string[]|array<string, string> $languages Either a list of language codes, or the return from
     *                                                  {@see lang_utils::get_language_labels}.
     */
    public function __construct(
        string $name,
        /** @var string[]|array<string, string> $languages */
        private readonly array $languages
    ) {
        parent::__construct($name, appendName: false, attributes: ['class' => 'row mt-4']);
    }

    #[\Override]
    public function _createElements(): void {
        $labelled_languages = array_is_list($this->languages)
            ? lang_utils::get_language_labels($this->languages)
            : $this->languages;

        foreach ($labelled_languages as $text) {
            // The static element doesn't work in groups, so we use the html element.
            $this->_elements[] = $this->createFormElement('html', '<div class="col"><h6>' . s($text) . '</h6></div>');
        }
    }

    #[\Override]
    public function exportValue(&$submitValues, $assoc = false): ?array {
        // We don't have a value.
        return null;
    }
}

// phpcs:enable
