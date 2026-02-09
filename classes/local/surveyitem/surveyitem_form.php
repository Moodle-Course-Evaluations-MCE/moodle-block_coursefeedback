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
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem;

use block_coursefeedback\local\form\multilang_header_element;
use block_coursefeedback\local\form\multilang_input_element;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\lang_utils;
use core\exception\coding_exception;
use HTML_QuickForm_element;
use HTML_QuickForm_group;
use lang_string;
use moodle_url;
use MoodleQuickForm_text;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class surveyitem_form extends \moodleform {

    /**
     * Initialize a new form.
     *
     * @param moodle_url $action
     * @param surveypart $surveypart
     * @param string $text_label_string The name of a different string to use for the 'text' editor labels.
     */
    public function __construct(
        moodle_url $action,
        /** @var surveypart $surveypart */
        protected readonly surveypart $surveypart,
        /** @var surveypart $surveypart */
        private readonly string $text_label_string = 'question_in_lang'
    ) {
        parent::__construct($action, [
            'surveypart' => $surveypart,
        ]);
    }

    /**
     * Adds elements for the question text to be used by most elements.
     *
     * @return void
     * @throws \coding_exception
     * @throws coding_exception
     */
    #[\Override]
    protected function definition(): void {
        $mform =& $this->_form;

        // Add a radio to select the currently shown languages. This isn't used server-side, but in a hideIf.
        $languages = $this->surveypart->get_languages();
        $radioarray = [
            $mform->createElement('radio', 'current_language', '', get_string('show_all_languages', 'block_coursefeedback'), "all"),
        ];
        foreach ($languages as $language) {
            $radioarray[] = $mform->createElement(
                'radio',
                'current_language',
                '',
                lang_utils::get_language_label($language),
                $language
            );
        }
        $mform->addGroup($radioarray, 'current_language_group', appendName: false, attributes: ['class' => 'm-1']);
        $mform->setDefault('current_language', 'all');

        // Add one editor for each language. We could look into writing a custom tinymce plugin for this at some point, or
        // integrating with tiny_multilang2 if it is installed.
        foreach ($languages as $language) {
            $editor_name = "text[$language]";
            $mform->addElement('editor', $editor_name, get_string($this->text_label_string, 'block_coursefeedback', $language));
            $mform->setType($editor_name, PARAM_RAW);

            $mform->hideIf($editor_name, 'current_language', 'in', array_diff($languages, [$language]));
        }
    }

    #[\Override]
    protected function after_definition(): void {
        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['text'])) {
            $used_formats = array_unique(array_map(fn($editor) => $editor['format'], $data['text']));
            if (count($used_formats) > 1) {
                foreach (array_keys($data['text']) as $language) {
                    $errors["text[$language]"] = get_string('inconsistent_editor_formats', 'block_coursefeedback');
                }
            }
        }

        return $errors;
    }
}
