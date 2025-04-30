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
 * This file keeps track of upgrades to block_coursefeedback.
 *
 * @package    block_coursefeedback
 * @copyright  2023 innoCampus, Technische Universität Berlin
 * @author     2011-2023 onwards Jan Eberhardt
 * @author     2022 onwards Felix Di Lenarda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for block_coursefeedback.
 *
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_block_coursefeedback_upgrade($oldversion = 0) {
    global $CFG, $DB;

    // TODO remove "unsigned" usage from all fields https://tracker.moodle.org/browse/MDL-27982 ???
    $dbman = $DB->get_manager();

    if ($oldversion < 2022092000) {

        // Define table block_coursefeedback_uidansw to be created.
        $table = new xmldb_table('block_coursefeedback_uidansw');

        // Adding fields to table block_coursefeedback_uidansw.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('course', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null, 'userid');
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'course');
        $table->add_field('coursefeedbackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'questionid');

        // Adding keys to table block_coursefeedback_uidansw.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('coursefeedbackid', XMLDB_KEY_FOREIGN, ['coursefeedbackid'], 'block_coursefeedback', ['id']);

        // Adding indexes to table block_coursefeedback_uidansw.
        $table->add_index('block_cfb_uscoqucf_i', XMLDB_INDEX_UNIQUE, ['userid', 'course', 'questionid', 'coursefeedbackid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2022092000, 'coursefeedback');
    }

    if ($oldversion < 2022101802) {
        // Add 'infotext' field to the 'block_coursefeedback' table

        $table = new xmldb_table('block_coursefeedback');
        $field = new xmldb_field('infotext', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2022101802, 'coursefeedback');
    }

    if ($oldversion < 2022102401) {
        // Drop 'userid' field of the 'block_coursefeedback_answers' table

        $table = new xmldb_table('block_coursefeedback_answers');

        // drop index
        $index = new xmldb_index('block_cfb_uidcoufid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid', 'course', 'coursefeedbackid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // drop field
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '1', true, XMLDB_NOTNULL, null, 1, 'id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_block_savepoint(true, 2022102401, 'coursefeedback');
    }

    if ($oldversion < 2022120200) {

        $anstable = new xmldb_table('block_coursefeedback_answers');
        $atuuidtable = new xmldb_table('block_coursefeedback_uidansw');

        // Remove key questionid.
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'block_coursefeedback_questns', ['id']);
        $dbman->drop_key($anstable, $key);
        $dbman->drop_key($atuuidtable, $key);

        // Add missing keys
        $nkey = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);
        $dbman->add_key($anstable, $nkey);
        $dbman->add_key($atuuidtable, $nkey);

        $nnkey = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $dbman->add_key($atuuidtable, $nnkey);

        $cfbkey = new xmldb_key('coursefeedbackid', XMLDB_KEY_FOREIGN, ['coursefeedbackid'], 'block_coursefeedback', ['id']);
        $dbman->add_key($atuuidtable, $cfbkey);

        // drop wrong index
        $windex = new xmldb_index('block_cfb_couqidans_idx', XMLDB_INDEX_NOTUNIQUE, ['course', 'questionid', 'answer']);
        if ($dbman->index_exists($anstable, $windex)) {
            $dbman->drop_index($anstable, $windex);
        }

        // add missing index
        $mindex = new xmldb_index('block_cfb_coufbidqidans_i', XMLDB_INDEX_NOTUNIQUE,
            ['course', 'coursefeedbackid', 'questionid', 'answer']);
        $dbman->add_index($anstable, $mindex);

        upgrade_block_savepoint(true, 2022120200, 'coursefeedback');
    }

    if ($oldversion < 2022120800) {
        // Add 'heading' field to the 'block_coursefeedback' table

        $table = new xmldb_table('block_coursefeedback');
        $field = new xmldb_field('heading', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2022120800, 'coursefeedback');
    }

    if ($oldversion < 2023011400) {
        // Add 'infotextformat' field to the 'block_coursefeedback' table

        $table = new xmldb_table('block_coursefeedback');
        $field = new xmldb_field('infotextformat', XMLDB_TYPE_INTEGER, 2);

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2023011400, 'coursefeedback');
    }

    if ($oldversion < 2023011800) {

        // For the upgrade to Version 2 we delete all instances of the coursefeedback block.
        // Since Version 2 we only allow one "context system" block which is displayed on all courses.
        // Only mangers are allowed now to add or delete the block.
        // We want exactly one block in each course.
        // To make things as easy as possible
        // we automatically add the block when upgradind to version 2 or installing the block.
        // There is a manual way to add the block  which is described in the REAMDE file.
        // Since we are adding the block automatically,
        // manually adding the block is only needed if the block was manually deleted before for some reason.

        // Delete all block_instances
        $blockinstances = $DB->get_records('block_instances', ['blockname' => 'coursefeedback']);
        foreach ($blockinstances as $block) {
            blocks_delete_instance($block);
        }

        // Add the wanted block
        $page = new moodle_page();
        $systemcontext = context_system::instance();
        $page->set_context($systemcontext);
        $page->blocks->add_region(BLOCK_POS_RIGHT);
        $page->blocks->add_block('coursefeedback', BLOCK_POS_RIGHT, 0, true, 'course-view-*');

        upgrade_block_savepoint(true, 2023011800, 'coursefeedback');
    }

    if ($oldversion < 2023122200) {
        // Add 'questiontype' field to table 'block_coursefeedback_questns'
        $table = new xmldb_table('block_coursefeedback_questns');
        $field = new xmldb_field('questiontype', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 1, 'questionid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2023122000, 'coursefeedback');
    }

    if ($oldversion < 2024010900) {
        // Create new table 'block_coursefeedback_essayanswers'.
        $table = new xmldb_table('block_coursefeedback_textans');

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('coursefeedbackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('textanswer', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('coursefeedbackid', XMLDB_KEY_FOREIGN, ['coursefeedbackid'], 'block_coursefeedback', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);

        // Conditionally create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2024010900, 'coursefeedback');
    }

    if ($oldversion == 2024015000 ) {
        // Following changes need to be checked only for version '2024015000'.

        $dbman = $DB->get_manager();
        $table = new xmldb_table('block_coursefeedback_answers');

        // Drop 'textanswer' field in 'block_coursefeedback_answers'.
        $field = new xmldb_field('textanswer', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'answer');
        $dbman->drop_field($table, $field);

        // Check if the 'answer' field in the table 'block_coursefeedback_answers' has the correct 'NOTNULL' value.
        $field = new xmldb_field('answer', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'questionid');
        // Drop index temporarily to make field change possible.
        $index = new xmldb_index('bloccouransw_coucouque_ix', XMLDB_INDEX_NOTUNIQUE, ['course', 'coursefeedbackid', 'questionid', 'answer']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Make sure NOTNULL-Option of the 'answer' field is 'true'.
        $dbman->change_field_notnull($table, $field);

        // Add index again.
        $dbman->add_index($table, $index);

        upgrade_block_savepoint(true, 2024015001, 'coursefeedback');
    }

    return true;
}

