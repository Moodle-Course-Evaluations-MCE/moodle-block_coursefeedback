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

/**
 * This file keeps track of upgrades to block_coursefeedback.
 *
 * @package    block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script for the Course Feedback block.
 *
 * @param int $oldversion The version number currently installed.
 * @return bool True on success.
 * @throws moodle_exception If the installed version is too old or confirmation is missing.
 */
function xmldb_block_coursefeedback_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    $dbman = $DB->get_manager();

    // 1) Enforce minimum starting version.
    if ($oldversion < 2025_05_09_00) {
        throw new coding_exception(
            'This upgrade requires plugin version 2025050900 or higher. '
            . 'Please upgrade to version 2025050900 before proceeding.'
        );
    }

    // 2) Verify that the admin has accepted the major overhaul and possible data loss.
    if ($oldversion < 2025050901) {
        $confirmed = get_config('block_coursefeedback', 'confirmoverhaul');
        if (empty($confirmed)) {
            throw new coding_exception(
                'You must confirm the major overhaul and possible data loss '
                . 'in the Course Feedback block settings before continuing.'
            );
        }

        // Define table block_coursefeedback to be renamed to block_coursefeedback_old.
        $table = new xmldb_table('block_coursefeedback');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback.
            $dbman->rename_table($table, 'block_coursefeedback_old');
        }

        // Define table block_coursefeedback_questns to be renamed to block_coursefeedback_old_questns.
        $table = new xmldb_table('block_coursefeedback_questns');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_questns.
            $dbman->rename_table($table, 'block_coursefeedback_old_questns');
        }

        // Define table block_coursefeedback_answers to be renamed to block_coursefeedback_old_answers.
        $table = new xmldb_table('block_coursefeedback_answers');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_answers.
            $dbman->rename_table($table, 'block_coursefeedback_old_answers');
        }

        // Define table block_coursefeedback_textans to be renamed to block_coursefeedback_old_textans.
        $table = new xmldb_table('block_coursefeedback_textans');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_textans.
            $dbman->rename_table($table, 'block_coursefeedback_old_textans');
        }

        // Define table block_coursefeedback_uidansw to be renamed to block_coursefeedback_old_uidansw.
        $table = new xmldb_table('block_coursefeedback_uidansw');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_uidansw.
            $dbman->rename_table($table, 'block_coursefeedback_old_uidansw');
        }

        upgrade_block_savepoint(true, 2025050901, 'coursefeedback');
    }
    if ($oldversion < 2025070802) {

        // 1) block_coursefeedback_organization
        $table = new xmldb_table('block_coursefeedback_organization');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name',         XMLDB_TYPE_TEXT,    null,  null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_key('primary',       XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // 2) block_coursefeedback_organization_coursecat
        $table = new xmldb_table('block_coursefeedback_organization_coursecat');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('coursecatid',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_key('primary',          XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('coursecatid_fk',   XMLDB_KEY_FOREIGN, ['coursecatid'], 'course_categories', ['id']);
        $table->add_key('organizationid_fk',XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('usermodified_fk',  XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // 3) block_coursefeedback_organization_user
        $table = new xmldb_table('block_coursefeedback_organization_user');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid',       XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_key('primary',          XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk',        XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('organizationid_fk',XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('usermodified_fk',  XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2025070802, 'coursefeedback');
    }
    if ($oldversion < 2025090100) {

        // Define table block_coursefeedback_survey to be created.
        $table = new xmldb_table('block_coursefeedback_survey');

        // Adding fields to table block_coursefeedback_survey.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_survey.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_survey.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_organization_survey to be created.
        $table = new xmldb_table('block_coursefeedback_organization_survey');

        // Adding fields to table block_coursefeedback_organization_survey.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_organization_survey.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('surveyid_fk', XMLDB_KEY_FOREIGN, ['surveyid'], 'block_coursefeedback_survey', ['id']);
        $table->add_key('organizationid_fk', XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_organization_survey.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Block_coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2025090100, 'coursefeedback');
    }

    return true;
}
