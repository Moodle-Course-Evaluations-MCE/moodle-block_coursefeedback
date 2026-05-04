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

namespace block_coursefeedback\event;

use coding_exception;
use moodle_url;

/**
 * Event to record that a user deleted the responses to a survey execution.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_responses_deleted extends \core\event\base {

    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('survey_responses_deleted', 'block_coursefeedback');
    }

    #[\Override]
    public function get_description(): string {
        $surveyexecutionid = $this->other['surveyexecutionid'];
        return "The user with id '$this->userid' deleted the responses of the survey execution with id '$surveyexecutionid' in " .
            "the course with id '$this->courseid'.";
    }

    #[\Override]
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['surveyexecutionid'])) {
            throw new coding_exception("The 'surveyexecutionid' value must be set in other.");
        }
    }

    #[\Override]
    public function get_url(): moodle_url {
        return new moodle_url('/blocks/coursefeedback/course.php', ['id' => $this->courseid]);
    }
}
