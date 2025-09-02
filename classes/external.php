<?php

namespace block_coursefeedback;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_value;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;

class external extends external_api
{
    public static function form_coursecat_selector_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'search' => new external_value(PARAM_RAW, 'Suchstring', VALUE_DEFAULT, '')
        ]);
    }

    public static function form_coursecat_selector(string $search = ''): array
    {
        $params = self::validate_parameters(
            self::form_coursecat_selector_parameters(),
            ['search' => $search]
        );
        $allcats = \core_course_category::make_categories_list();
        $results = [];
        foreach ($allcats as $id => $name) {
            if ($search === '' || stripos($name, $search) !== false) {
                $results[] = ['id' => $id, 'text' => $name];
            }
        }
        return ['categories' => $results];
    }

    public static function form_coursecat_selector_returns(): external_single_structure
    {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Kategorie-ID'),
                    'text' => new external_value(PARAM_TEXT, 'Anzeigename')
                ])
            )
        ]);
    }
}
