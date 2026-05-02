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

namespace block_coursefeedback\output;

use block_coursefeedback\local\course_feedback_data;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\survey_page;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\output\named_templatable;
use core\output\renderer_base;
use renderable;

/**
 * A templatable for a survey.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey implements named_templatable, renderable {

    /**
     * Private constructor.
     *
     * @param survey_page[] $pages
     * @param array<int, response_slot[]> $slots_by_spe_id
     * @param int|null $courseid
     */
    private function __construct(
        /** @var survey_page[] $pages */
        private readonly array $pages,
        /** @var array<int, response_slot[]> $slots_by_spe_id */
        private readonly array $slots_by_spe_id,
        /** @var int|null $courseid */
        private readonly ?int $courseid,
        /** @var string|null $append_to_selector */
        private readonly ?string $append_to_selector,
    ) {
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'block_coursefeedback/survey/root';
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        // For all SPEs with only one slot, initialize the selected slot with it.
        $default_slots = [];
        foreach ($this->slots_by_spe_id as $spe_id => $slots) {
            if (count($slots) === 1) {
                $default_slots[$spe_id] = $slots[0]->get('id');
            }
        }

        $json_data = [
            "pages" => $this->pages,
            "default_slots" => $default_slots,
        ];
        if ($this->courseid) {
            $json_data["courseid"] = $this->courseid;
        }

        $context = [
            'first_page' => $this->pages[0] ?? null,
            'amount_pages' => count($this->pages),
            'json_data' => json_encode($json_data),
        ];
        $context['append_to_selector'] = $this->append_to_selector;
        return $context;
    }

    /**
     * Create an instance to display the survey for the given course.
     *
     * @param course_feedback_data $course_data
     * @return self
     */
    public static function for_course(course_feedback_data $course_data): self {
        $events_by_spe_id = [];
        foreach ($course_data->spes_by_event_id as $event_id => $spe) {
            $events_by_spe_id[$spe->get('id')] = $course_data->events_by_id[$event_id];
        }

        $pages = surveyitem_manager::export_pages_for_survey(
            surveyparts: array_values($course_data->survey_parts_by_spe_id),
            spes: array_values($course_data->spes_by_event_id),
            events_by_spe_id: $events_by_spe_id,
            slots_by_spe_id: $course_data->slots_by_spe_id
        );

        return new self(
            $pages,
            $course_data->slots_by_spe_id,
            $course_data->course->id,
            "#user-notifications"
        );
    }

    /**
     * Create an instance only showing the given surveypart so that users may test it.
     *
     * There is no association to a course and answers will never be saved.
     *
     * @param surveypart $surveypart
     * @return self
     */
    public static function for_testing_surveypart(surveypart $surveypart): self {
        // Negative IDs prevent accidentally saving the slot or SPE.
        $spe = (new survey_part_execution())->set_many([
            "id" => -1,
            "surveyexecutionid" => -1,
            "surveypartid" => $surveypart->get('id'),
        ]);
        $slots = [
            $spe->get('id') => [
                (new response_slot())->set_many([
                    "id" => -1,
                    "surveypartexecutionid" => $spe->get('id'),
                    "name" => "-",
                ]),
            ],
        ];

        $pages = surveyitem_manager::export_pages_for_survey(
            surveyparts: [$surveypart],
            spes: [$spe],
            events_by_spe_id: [],
            slots_by_spe_id: $slots
        );

        return new self($pages, $slots, courseid: null, append_to_selector: null);
    }

    /**
     * Check if this survey is empty. A survey is considered empty if it has no pages or all pages are empty.
     *
     * @return bool
     */
    public function is_empty(): bool {
        // TODO: Also consider a survey empty when all items are autogenerated.
        // Or maybe don't add those if there are no proper items.
        return !array_filter($this->pages, fn($page) => !empty($page->items));
    }
}
