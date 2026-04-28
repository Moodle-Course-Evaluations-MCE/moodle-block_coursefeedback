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

namespace block_coursefeedback\local\persistent;

use core\exception\coding_exception;
use core\persistent;

/**
 * Response slot persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_slot extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveypartexecutionoption';

    #[\Override]
    protected static function define_properties(): array {
        return [
            'surveypartexecutionid' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'externalid' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Sets the users who will be allowed to see the responses in this slot.
     *
     * @param int[] $userids
     * @return void
     */
    public function set_users(array $userids): void {
        if (!$this->get('id')) {
            throw new coding_exception("Cannot set response slot users before inserting response slot.");
        }

        response_slot_user::diff_create_delete(
            conditions: [ 'surveypartexecutionoptionid' => $this->get('id') ],
            value_field: 'userid',
            values: $userids,
        );
    }
}
