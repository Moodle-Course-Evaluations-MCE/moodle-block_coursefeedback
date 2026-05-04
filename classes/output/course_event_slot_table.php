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

namespace block_coursefeedback\output;

use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use core\exception\coding_exception;
use core\output\named_templatable;
use core\output\renderable;
use core\output\renderer_base;

/**
 * Renderable of a course's event, slot, and slot user table that is shown in the feedback settings for a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_event_slot_table implements named_templatable, renderable {

    /** @var eventtype[]|null */
    private ?array $availableeventtypes = null;

    /** @var surveypart[]|null */
    private ?array $availablesurveyparts = null;

    /** @var array<int, object>|null */
    private ?array $enrolledusers = null;

    /**
     * Initialize a new instance.
     *
     * @param survey_execution_data $survey_data
     * @param object $course
     */
    public function __construct(
        /** @var survey_execution_data $survey_data */
        private readonly survey_execution_data $survey_data,
        /** @var object $course */
        private readonly object $course,
    ) {
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'block_coursefeedback/course_event_slot_table';
    }

    /**
     * Exports the view model for a single slot.
     *
     * @param renderer_base $output
     * @param response_slot $slot
     * @param bool $allow_deletion
     * @return array
     */
    private function export_slot(renderer_base $output, response_slot $slot, bool $allow_deletion): array {
        if ($this->enrolledusers === null) {
            $this->enrolledusers = array_column(enrol_get_course_users($this->course->id), null, 'id');
        }

        $users_editable = new slot_users_editable(
            slot: $slot,
            availableusers: $this->enrolledusers,
            assignedusers: $this->survey_data->users_by_slot_id[$slot->get('id')] ?? [],
            editable: true,
        );

        return [
            'id' => $slot->get('id'),
            'name' => $slot->get('name'),
            'allow_deletion' => $allow_deletion,
            'users_editable_context' => $users_editable->export_for_template($output),
        ];
    }

    /**
     * Exports the view model for a single survey part execution.
     *
     * @param renderer_base $output
     * @param survey_part_execution $survey_part_execution
     * @return array
     */
    private function export_spe(renderer_base $output, survey_part_execution $survey_part_execution): array {
        $survey_part = $this->survey_data->survey_parts_by_spe_id[$survey_part_execution->get('id')];
        $slots = $this->survey_data->slots_by_spe_id[$survey_part_execution->get('id')] ?? [];
        $allow_slot_deletion = count($slots) > 1;
        $first_slot = array_shift($slots);
        return [
            'id' => $survey_part_execution->get('id'),
            'survey_part' => [
                'id' => $survey_part->get('id'),
                'name' => $survey_part->get('name'),
            ],
            'rowspan' => ($first_slot ? 1 : 0) + count($slots) + 1,
            'first_slot' => $first_slot ? $this->export_slot($output, $first_slot, $allow_slot_deletion) : null,
            'more_slots' => array_map(fn($slot) => $this::export_slot($output, $slot, $allow_slot_deletion), $slots),
        ];
    }

    /**
     * Exports the view model for the available event types, marking the selected one, if any.
     *
     * @param int|null $selectedid
     * @return array
     */
    private function export_available_event_types(?int $selectedid = null): array {
        if ($this->availableeventtypes === null) {
            $this->availableeventtypes = eventtype::get_records(['active' => true]);
        }

        return array_map(fn($type) => [
            'id' => $type->get('id'),
            'name' => $type->get('name'),
            'selected' => $selectedid === $type->get('id'),
        ], $this->availableeventtypes);
    }

    /**
     * Exports the view model for the available event types, marking the selected one, if any.
     *
     * @param int|null $selectedid
     * @return array
     */
    private function export_available_survey_parts(?int $selectedid = null): array {
        if ($this->availablesurveyparts === null) {
            $this->availablesurveyparts = surveypart::get_records();
        }

        return array_map(fn($survey_part) => [
            'id' => $survey_part->get('id'),
            'name' => $survey_part->get('name'),
            'selected' => $selectedid === $survey_part->get('id'),
        ], $this->availablesurveyparts);
    }

    /**
     * Exports the view model for a single teaching event.
     *
     * @param renderer_base $output
     * @param teaching_event $event
     * @return array
     */
    private function export_event(renderer_base $output, teaching_event $event): array {
        $id = $event->get('id');
        $spe = $this->survey_data->spes_by_event_id[$id] ?? null;
        if (!$spe) {
            throw new coding_exception("Event '$id' has no survey part execution.");
        }
        return [
            'id' => $id,
            'name' => $event->get('name'),
            'type' => [
                'name' => $event->get('eventtypeid') ?
                    $this->survey_data->types_by_event_id[$id]->get('name') :
                    get_string('default', 'block_coursefeedback'),
            ],
            'available_event_types' => $this->export_available_event_types(selectedid: $event->get('eventtypeid')),
            'survey_part_execution' => $this->export_spe($output, $spe),
            'available_survey_parts' => $this->export_available_survey_parts(selectedid: $spe->get('surveypartid')),
        ];
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        return [
            'course' => [
                'id' => $this->course->id,
                'shortname' => $this->course->shortname,
            ],
            'survey_execution' => [
                'id' => $this->survey_data->survey_execution->get('id'),
            ],
            'events' => array_map(
                fn($event) => self::export_event($output, $event),
                array_values($this->survey_data->events_by_id)
            ),
            'available_event_types' => $this->export_available_event_types(),
            'available_survey_parts' => $this->export_available_survey_parts(),
        ];
    }
}
