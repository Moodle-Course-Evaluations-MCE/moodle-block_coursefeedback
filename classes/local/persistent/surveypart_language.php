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

use coding_exception;
use core\persistent;
use dml_exception;

/**
 * Persistent class for a language enabled in a surveypart.
 *
 * @see surveypart::get_languages()
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveypart_language extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveypart_language';

    #[\Override]
    protected static function define_properties(): array {
        return [
            'language' => [
                // TODO: PARAM_LANG? That would restrict this to languages installed in the site and might lead to issues when
                // languages are uninstalled from the site.
                'type' => PARAM_ALPHAEXT,
            ],
            'surveypartid' => [
                'type' => PARAM_INT,
            ],
        ];
    }
}
