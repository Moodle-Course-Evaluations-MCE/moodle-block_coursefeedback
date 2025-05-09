<?php
declare(strict_types=1);

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

/**
 * This file keeps track of upgrades to block_coursefeedback.
 *
 * @package    block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for the Course Feedback block.
 *
 * @param int $oldversion The version number currently installed.
 * @return bool True on success.
 * @throws moodle_exception If the installed version is too old or confirmation is missing.
 */
function xmldb_block_coursefeedback_upgrade(int $oldversion): bool
{
    global $DB;

    // 1) Enforce minimum starting version.
    if ($oldversion < 2025050900) {
        throw new moodle_exception(
            'This upgrade requires plugin version 2025050900 or higher. '
            . 'Please upgrade to version 2025050900 before proceeding.',
            'block_coursefeedback'
        );
    }

    // 2) Verify that the admin has accepted the major overhaul and possible data loss.
    if ($oldversion < 2025050901) {
        $confirmed = get_config('block_coursefeedback', 'confirmoverhaul');
        if (empty($confirmed)) {
            throw new moodle_exception(
                'You must confirm the major overhaul and possible data loss '
                . 'in the Course Feedback block settings before continuing.'
            );
        }

        // TODO: data archival/migration logic here.

        upgrade_plugin_savepoint(true, 2025050901, 'block_coursefeedback');
    }

    return true;
}
