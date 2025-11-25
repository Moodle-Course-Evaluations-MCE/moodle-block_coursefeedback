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
 * Surveypart persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\persistent;

use core\exception\coding_exception;
use core\persistent;

/**
 * Surveypart persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveypart extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveypart';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
        ];
    }

    /**
     * Returns the ids of all surveyitems belonging to this surveypart.
     * @return int[]
     */
    public function get_surveyitemids(): array {
        global $DB;

        return $DB->get_fieldset(
            'block_coursefeedback_surveyitem',
            'id',
            ['surveypartid' => $this->get('id')]
        );
    }

    /**
     * Returns all surveyitemds belonging to this surveypart.
     * @return surveyitem[]
     */
    public function get_surveyitems(): array {
        return surveyitem::get_records(
            ['surveypartid' => $this->get('id')],
            'sortindex'
        );
    }

    /**
     * Reorders the surveyitems according to the given $itemids.
     * @param int[] $itemids Array of (all) surveyitemids in the desired order.
     * @return void
     */
    public function reorder_surveyitems(array $itemids) {
        global $DB;
        $existingids = array_flip($this->get_surveyitemids());

        if (count($itemids) !== count($existingids)) {
            throw new coding_exception('$itemids and $existingids have different lengths.');
        }
        foreach ($itemids as $itemid) {
            if (!array_key_exists($itemid, $existingids)) {
                throw new coding_exception('$itemids contains extraneous key ' . $itemid);
            }
        }

        $transaction = $DB->start_delegated_transaction();

        for ($i = 0; $i < count($itemids); $i++) {
            $DB->update_record('block_coursefeedback_surveyitem', [
                'id' => $itemids[$i],
                'sortindex' => $i,
            ], true);
        }

        $transaction->allow_commit();
    }
}
