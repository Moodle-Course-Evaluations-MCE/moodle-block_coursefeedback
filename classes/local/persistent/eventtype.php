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
 * Event type persistent class
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\persistent;

use block_coursefeedback\local\multilang_string;
use core\persistent;

/**
 * Event type persistent class
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eventtype extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_eventtype';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'active' => [
                'type' => PARAM_BOOL,
            ],
            'organizationid' => [
                'type' => PARAM_INT,
            ],
            'surveypartid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }
}
