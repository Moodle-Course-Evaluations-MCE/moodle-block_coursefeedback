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
class surveypart_language extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveypart_language';

    #[\Override]
    protected static function define_properties() {
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

    /**
     * Insert multiple instances at once, probably more efficiently than calling insert() multiple times.
     *
     * @param self[] $persistents
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function bulk_insert(array $persistents): void {
        foreach ($persistents as $persistent) {
            $persistent->before_create();

            $now = time();
            $persistent->raw_set('timecreated', $now);
            $persistent->raw_set('timemodified', $now);
            global $USER;
            $persistent->raw_set('usermodified', $USER->id);

            if ($persistent->get('id') >= 1) {
                throw new coding_exception('Cannot insert persistent that already has an ID.');
            }
        }

        global $DB;
        $DB->insert_records(static::TABLE, array_map(fn($persistent) => $persistent->to_record(), $persistents));

        foreach ($persistents as $persistent) {
            $persistent->after_create();
        }
    }

    /**
     * Delete multiple instances at once, probably more efficiently than calling insert() multiple times.
     *
     * @param self[] $persistents
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function bulk_delete(array $persistents): void {
        foreach ($persistents as $persistent) {
            $persistent->before_delete();
            if ($persistent->get('id') <= 0) {
                throw new coding_exception('Cannot delete persistent that has no ID.');
            }
        }

        global $DB;
        $DB->delete_records_list(static::TABLE, 'id', array_map(fn($persistent) => $persistent->get('id'), $persistents));

        foreach ($persistents as $persistent) {
            $persistent->after_delete(true);
            $persistent->raw_set('id', 0);
        }
    }
}
