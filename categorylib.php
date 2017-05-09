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

/**
 * Part one of recursive function pair to print out all the categories in a nice format with courses included.
 * 
 * @package   gradereport_multigrader
 * @copyright 2012 onwards Barry Oosthuizen http://elearningstudio.co.uk
 * @author    Barry Oosthuizen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 * @global stdClass $CFG
 * @param stdClass $category
 * @param int $displaylist
 * @param bool $files
 * @return type
 */
function gradereport_multigrader_print_category($category = null, $displaylist = null, $files = true) {
    global $CFG;

    if ($category) {
        if ($category->visible or has_capability('moodle/course:update', context_system::instance())) {
            echo "<li>\n";
            gradereport_multigrader_print_category_info($category, $files);
            gradereport_print_category_content($category->id, $displaylist, $files);
            echo "</li>\n";
        } else {
            return;  // Don't bother printing children of invisible categories
        }
    } else {
        $category = new stdClass();
        $category->id = "0";
        gradereport_print_category_content($category->id, $displaylist, $files);
    }
}

/**
 * Part two of recursive function pair to print out all the categories in a nice format with courses included.
 * 
 * @package   gradereport_multigrader
 * @copyright 2012 onwards Barry Oosthuizen http://elearningstudio.co.uk
 * @author    Barry Oosthuizen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 * @global stdClass $CFG
 * @param int $categoryid
 * @param int $displaylist
 * @param bool $files
 * @return type
 */
function gradereport_print_category_content($categoryid=0, $displaylist=null, $files=true) {
    if ($categories = coursecat::get($categoryid)->get_children()) {   // Print all the children recursively
        echo "<ul>\n";
        foreach ($categories as $cat) {
            gradereport_multigrader_print_category($cat, $displaylist, $files);
        }
        echo "</ul>\n";
    }    
}

/**
 * Prints the category info in indented fashion
 * This function is only used by print_whole_category_list() above
 * 
 * @global stdClass $CFG
 * @global stdClass $DB
 * @param object $category
 * @param bool $files
 */
function gradereport_multigrader_print_category_info($category, $files = false) {
    global $CFG, $DB;

    $coursecount = $DB->count_records('course') <= $CFG->frontpagecourselimit;
    $i = 0;

    $courses = get_courses($category->id, 'c.sortorder ASC', 'c.id,c.sortorder,c.visible,c.fullname,c.shortname');
    if ($category->visible) {
        echo '<input type="checkbox" name="category" id="catid' . $category->id . '"/>';
        echo '<label>' . format_string($category->name) . '</label>';
    } else {
        echo '<input type="checkbox" name="hiddencategory" id="catid' . $category->id . '"/>';
        echo '<label class="dimmed">' . format_string($category->name) . '</label>';
    }

    if ($files and $coursecount) {
        if ($courses) {
            echo "<ul>\n";
            foreach ($courses as $course) {
                echo "<li>\n";
                if ($course->visible) {
                    echo '<input type="checkbox" name="coursebox[]" value="' . $course->id . '"/>';
                    echo '<label>' . format_string($course->shortname) . '</label>';
                } else {
                    echo '<input type="checkbox" name="coursebox[]" value="' . $course->id . '"/>';
                    echo '<label class="dimmed">' . format_string($course->shortname) . '</label>';
                }
                echo "</li>\n";
            }
            echo "</ul>\n";
        }
    }
}
