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

use block_coursefeedback\local\multilang_string;
use block_coursefeedback\local\lang_utils;
use coding_exception;
use HTML_QuickForm_element;
use HTML_QuickForm_text;
use HTML_QuickForm_utils;
use invalid_parameter_exception;
use MoodleQuickForm_group;
use MoodleQuickForm_text;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/group.php');

// Pre-existing conditions...
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

/**
 * A group element showing text inputs for the given languages. Uses {@see multilang_string} for the value.
 *
 * @see multilang_string
 * @see multilang_header_element
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class multilang_input_element extends MoodleQuickForm_group {

    /**
     * Initializes a new instance.
     *
     * @param string $name
     * @param string $row_label The label for the whole row.
     * @param array<string, string> $labels_by_languages The labels to assign to the individual inputs. These will be hidden, but
     *                                                   available to screen readers and as tooltips.
     */
    public function __construct(
        string $name,
        string $row_label,
        /** @var array<string, string> $labels_by_languages */
        private readonly array $labels_by_languages
    ) {
        parent::__construct($name, $row_label, appendName: false, attributes: ['class' => 'row']);
    }

    #[\Override]
    public function _createElements(): void {
        /** @var multilang_string|null $value */
        $value = $this->_findValue($this->_mform->_constantValues)
            ?? $this->_findValue($this->_mform->_submitValues)
            ?? $this->_findValue($this->_mform->_defaultValues);

        foreach ($this->labels_by_languages as $language => $input_label) {
            /** @var MoodleQuickForm_text $element */
            $element = $this->_elements[] = $this->createFormElement('text', "{$this->getName()}[$language]", $input_label, [
                'class' => 'col',
                'title' => $input_label,
                'placeholder' => get_string('not_translated', 'block_coursefeedback'),
            ]);
            $element->setHiddenLabel(true);

            if ($value && isset($value->translations[$language])) {
                $element->setValue($value->translations[$language]);
            }

            $this->_mform->setType($element->getName(), PARAM_TEXT);
        }
    }

    /**
     * Adds a rule requiring at least one translation to be filled in.
     *
     * @return void
     */
    public function require_at_least_one_translation(): void {
        $this->_mform->addGroupRule(
            $this->getName(),
            get_string('at_least_one_translation_required', 'block_coursefeedback'),
            type: 'required',
            howmany: 1,
            validation: 'client'
        );
    }

    #[\Override]
    public function exportValue(&$submitValues, $assoc = false) {
        $raw_array = parent::exportValue($submitValues, $assoc);

        $values_by_language = $this->_findValue($raw_array);
        if ($values_by_language === null) {
            return $raw_array;
        }

        $cleaned = array_intersect_key($values_by_language, $this->labels_by_languages);
        $cleaned = array_map(fn($value) => validate_param($value, PARAM_TEXT), $cleaned);

        $text = multilang_string::from_array($cleaned);
        if (!$text) {
            // If no translations or all translations empty, consider this element as not submitted.
            return null;
        }

        return $this->_prepareValue($text, $assoc);
    }

    #[\Override]
    public function setValue($value): void {
        if (is_string($value)) {
            $value = multilang_string::deserialize($value);
        }

        if ($value instanceof multilang_string) {
            /** @var array<string, HTML_QuickForm_text> $elements_by_langs */
            $elements_by_langs = array_combine(array_keys($this->labels_by_languages), $this->_elements);
            foreach ($elements_by_langs as $lang => $element) {
                $element->onQuickFormEvent('setGroupValue', $value->translations[$lang] ?? null, $this);
            }
        }
    }

    #[\Override]
    public function onQuickFormEvent($event, $arg, &$caller): bool {
        if (!parent::onQuickFormEvent($event, $arg, $caller)) {
            return false;
        }

        if ($event === 'updateValue') {
            $value = $this->_findValue($caller->_constantValues)
                ?? $this->_findValue($caller->_submitValues)
                ?? $this->_findValue($caller->_defaultValues);
            if ($value) {
                $this->setValue($value);
            }
        }

        return true;
    }
}
