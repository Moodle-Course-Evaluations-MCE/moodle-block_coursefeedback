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

/**
 * Survey execution persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveyexecution';

    /** @var int Survey is planned. */
    public const STATUS_PLANNED = 0;
    /** @var int Survey is active (and locked). The default event and surveypart was added, if there were no associated events. */
    public const STATUS_STARTED = 1;

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'starttime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'endtime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'status' => [
                'type' => PARAM_INT,
                'default' => 0,
                'choices' => [
                    self::STATUS_PLANNED,
                    self::STATUS_STARTED,
                ],
            ],
        ];
    }
}
