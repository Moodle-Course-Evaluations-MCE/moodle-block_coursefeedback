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
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\scalequestion;

use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_form;
use coding_exception;
use dml_exception;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scalequestion_form extends surveyitem_form {

    /**
     * Definition for the form.
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    #[\Override]
    protected function definition(): void {
        parent::definition();

        global $DB, $PAGE;
        $mform =& $this->_form;

        /** @var surveypart $surveypart */
        $surveypart = $this->_customdata['surveypart'];

        $PAGE->requires->js_call_amd('block_coursefeedback/create_new_scale_redirect', 'init', [$surveypart->get('id')]);

        $scales = $DB->get_records('block_coursefeedback_scale', ['surveypartid' => $surveypart->get('id')]);

        $options = [];
        foreach ($scales as $scale) {
            $options[$scale->id] = $scale->name;
        }

        if (empty($options)) {
            $options[-2] = '-';
        }
        $options[-1] = get_string('create_new_scale', 'block_coursefeedback');

        $mform->addElement('select', 'scaleid', get_string('scale', 'block_coursefeedback'), $options);

        $mform->addElement('checkbox', 'forceshowscale', get_string('forceshowscale', 'block_coursefeedback'));

        $mform->disable_form_change_checker();
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if ($data['scaleid'] <= 0) {
            $errors['scaleid'] = get_string('no_scale_selected', 'block_coursefeedback');
        }

        return $errors;
    }
}
