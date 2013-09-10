<?php
// This file is part of the Multi Course Grader report for Moodle by Barry Oosthuizen http://elearningstudio.co.uk
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

/*
 *
 * @package   gradereport_multigrader
 * @copyright 2012 onwards Barry Oosthuizen http://elearningstudio.co.uk
 * @author    Barry Oosthuizen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function gradereport_multigrader_print_category($category=NULL, $displaylist=NULL, $parentslist=NULL, $depth=-1, $files = true) {

    Global $CFG;
    /// Recursive function to print out all the categories in a nice format
    /// with or without courses included

    if (isset($CFG->max_category_depth) && ($depth >= $CFG->max_category_depth)) {
        return;
    }

    if ($category) {

        if ($category->visible or has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM))) {
            gradereport_multigrader_print_category_info($category, $depth, $files);
        } else {
            return;  // Don't bother printing children of invisible categories
        }

    } else {
        $category = new stdClass();
        $category->id = "0";
    }


    if ($categories = get_child_categories($category->id)) {   // Print all the children recursively
        $countcats = count($categories);
        $count = 0;
        $first = true;
        $last = false;

        foreach ($categories as $cat) {

            $count++;

            if ($count == $countcats) {
                $last = true;
            }

            $up = $first ? false : true;
            $down = $last ? false : true;
            $first = false;

            gradereport_multigrader_print_category($cat, $displaylist, $parentslist, $depth + 1, $files);

            echo '</ul></li>';
        }
    }
}

function gradereport_multigrader_print_category_info($category, $depth, $files = false) {
/// Prints the category info in indented fashion
/// This function is only used by print_whole_category_list() above
    Global $CFG; Global $DB;

    $coursecount = $DB->count_records('course') <= FRONTPAGECOURSELIMIT;
    $i = 0;

    $courses = get_courses($category->id, 'c.sortorder ASC', 'c.id,c.sortorder,c.visible,c.fullname,c.shortname');
    if ($depth) {
        if(!$i == 0) {
            echo '</li>';

            $i = 1;
        }

        if ($category->visible) {

            echo '<li><input type="checkbox" name="category" id="catid'.$category->id.'"/>';
            echo '<label>'.format_string($category->name).'</label>';

        } else {

            echo '<li><input type="checkbox" name="hiddencategory" id="catid'.$category->id.'"/>';
            echo '<label>'.format_string($category->name).'</label></li>';
        }

    } else {

        if(!$i == 0) {

            echo '</li>';

            $i = 1;
        }

        if ($category->visible) {

            echo '<li><input type="checkbox" name="category" id="catid'.$category->id.'"/>';
            echo '<label>'.format_string($category->name).'</label>';

        } else {

            echo '<li><input type="checkbox" name="hiddencategory" id="catid'.$category->id.'"/>';
            echo '<label>'.format_string($category->name).'</label></li>';

        }
    }


    if ($files and $coursecount) {

        echo '<ul>';

        if ($courses && !(isset($CFG->max_category_depth)&& ($depth>=$CFG->max_category_depth - 1))) {

            foreach ($courses as $course) {

                if ($course->visible) {

                    echo '<li><input type="checkbox" name="coursebox[]" value="'.$course->id.'"/>';
                    echo '<label>'.format_string($course->shortname).'</label></li>';

                } else {

                    echo '<li><input type="checkbox" name="coursebox[]" value="'.$course->id.'"/>';
                    echo '<label>'.format_string($course->shortname).'</label></li>';

                }
            }
        }
    }
}
?>