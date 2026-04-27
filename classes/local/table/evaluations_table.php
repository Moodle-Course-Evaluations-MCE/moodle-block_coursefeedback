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
 * Table for evaluations.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\table;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\persistent\organization;
use core\output\html_writer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

// PHPCS is confused by constructor parameters being promoted to class properties.
// phpcs:disable moodle.Commenting.VariableComment.Missing
/**
 * Table for evaluations.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluations_table extends no_pagination_table {

    /** @var object Some pre-fetched strings. */
    private object $strings;

    /**
     * Constructs the table of evaluations.
     * @var int $semester Semester as described in course_semester_mapping to filter by.
     * @param organization $organization Organization to filter by.
     */
    public function __construct(
        public readonly int $semester,
        private readonly organization $organization,
    ) {
        global $OUTPUT, $PAGE;
        parent::__construct('block_coursefeedback-evaluations');
        $this->define_baseurl($PAGE->url);
        $semester_join = course_semester_mapping::get_instance()::get_filter_sql_for_semester($this->semester);
        $organization_join = course_organization_mapping::get_instance()::get_filter_sql_for_organization($this->organization);
        $this->set_sql(
            "c.id as courseid, c.fullname as name, se.starttime, se.endtime, se.status ",
            "{course} c
            $semester_join->joins
            $organization_join->joins
            JOIN {block_coursefeedback_surveyexecution} se ON se.courseid = c.id",
            "$semester_join->wheres AND $organization_join->wheres",
            array_merge($semester_join->params, $organization_join->params),
        );
        $this->column_nosort = ['checkbox', 'tools'];
        $this->define_columns(['checkbox', 'name', 'starttime', 'status', 'tools']);
        $this->define_headers([
            $OUTPUT->render(new \core\output\checkbox_toggleall('coursefeedback-courses_with_evaluation', true, [
                'name' => 'select-all-courses_with_evaluation',
                'label' => get_string('selectall'),
                'labelclasses' => 'sr-only',
                'classes' => 'm-1',
                'checked' => false,
            ])),
            get_string('name', 'block_coursefeedback'),
            get_string('evaluation_period', 'block_coursefeedback'),
            get_string('status'),
            get_string('tools', 'block_coursefeedback'),
        ]);

        $this->strings = (object) array_merge(
            (array) get_strings([
                'create_default',
                'planned',
                'ongoing',
                'finished',
            ], 'block_coursefeedback'),
            (array) get_strings([
                'strftimedatetimeshort',
            ], 'core_langconfig'),
        );

        $PAGE->requires->js_call_amd('block_coursefeedback/bulkactions_post', 'init');
    }

    /**
     * Renders the checkbox column.
     * @param $row
     * @return string
     */
    public function col_checkbox($row) {
        global $OUTPUT;
        $checkbox = new \core\output\checkbox_toggleall('coursefeedback-courses_with_evaluation', false, [
            'classes' => 'usercheckbox m-1',
            'name' => 'coursefeedback-select',
            'value' => $row->courseid,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', $row->courseid),
            'labelclasses' => 'accesshide',
        ]);
        return $OUTPUT->render($checkbox);
    }

    /**
     * Renders the course name column.
     * @param $row
     * @return string
     */
    public function col_name($row) {
        return html_writer::link(
            course_get_url($row->courseid),
            $row->name,
        );
    }

    /**
     * Renders the evaluation period column.
     * @param $row
     * @return string
     */
    public function col_starttime($row) {
        $starttime = $row->starttime ?? $this->organization->get('default_evaluation_starttime');
        $endtime = $row->endtime ?? $this->organization->get('default_evaluation_endtime');
        return html_writer::span(
            userdate($starttime, $this->strings->strftimedatetimeshort)
            . ' - ' . userdate($endtime, $this->strings->strftimedatetimeshort),
            $starttime && $endtime ? 'fw-bold font-weight-bold' : 'text-muted'
        );
    }

    /**
     * Render the status column.
     * @param $row
     * @return string
     */
    public function col_status($row) {
        switch ($row->status) {
            case 0:
                return $this->strings->planned;
            case 1:
                return $this->strings->ongoing;
            case 2:
                return $this->strings->finished;
        }
        return '';
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_tools($row) {
        global $OUTPUT;
        $output = '';

        $alt = get_string('edit');
        $icon = 't/edit';
        $url = new \moodle_url('/blocks/coursefeedback/course.php', ['id' => $row->courseid]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

        return $output;
    }

    /**
     * Prints the action row with actions for selected rows. Is used above and below the table.
     */
    private function print_action_row() {
        global $OUTPUT;

        $actionmenu = new \action_menu();

        $actionmenu->set_menu_trigger(get_string('for_selected', 'block_coursefeedback'));
        echo $OUTPUT->render_action_menu($actionmenu);
    }

    #[\Override]
    public function wrap_html_start() {
        parent::wrap_html_start();
        $this->print_action_row();
        echo '<br>';
    }

    #[\Override]
    public function wrap_html_finish() {
        echo '<br>';
        $this->print_action_row();
        parent::wrap_html_finish();
    }
}
