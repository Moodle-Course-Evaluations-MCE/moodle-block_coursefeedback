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
 * Scale persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\persistent;

use core\persistent;

/**
 * Scale persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scale extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_scale';

    /**
     * Lists the fields which have (translatable) text in them.
     */
    public const TEXT_FIELDS = [
        'minoptiontext',
        'maxoptiontext',
        'noansweroptiontext',
    ];

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'surveypartid' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'optionamount' => [
                'type' => PARAM_INT,
            ],
            'minoptiontextid' => [
                'type' => PARAM_INT,
            ],
            'maxoptiontextid' => [
                'type' => PARAM_INT,
            ],
            'hasnoansweroption' => [
                'type' => PARAM_BOOL,
            ],
            'noansweroptiontextid' => [
                'type' => PARAM_INT,
                'required' => false,
                'default' => null,
            ],
        ];
    }
}
