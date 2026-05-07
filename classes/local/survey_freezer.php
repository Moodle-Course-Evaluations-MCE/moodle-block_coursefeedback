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

namespace block_coursefeedback\local;

use block_coursefeedback\local\persistent\survey_execution;
use core\exception\moodle_exception;

/**
 * Checks if a resource is frozen, i.e., can't be modified significantly due to active surveys.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_freezer {

    /**
     * Returns true if the survey execution is frozen, i.e., can't be modified in a structurally significant manner.
     *
     * @param survey_execution $se
     * @return bool
     */
    public function is_se_frozen(survey_execution $se): bool {
        return $se->get('status') == survey_execution::STATUS_STARTED;
    }

    /**
     * Throws if the survey execution contained in the record is frozen.
     *
     * @param survey_execution $se
     * @param string $action_debug_info To include in the debuginfo of the {@see moodle_exception}.
     * @return void
     */
    public function check_se_action(survey_execution $se, string $action_debug_info): void {
        if ($this->is_se_frozen($se)) {
            throw new moodle_exception(
                'survey_execution_frozen_short',
                'block_coursefeedback',
                debuginfo: "cannot $action_debug_info due to survey execution '{$se->get('id')}' in status '{$se->get('status')}'"
            );
        }
    }
}
