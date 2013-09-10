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
 * Outputs navigation tabs for the multi grader report
 *
 * @package   gradereport_multigrader
 * @copyright 2012 onwards Barry Oosthuizen http://elearningstudio.co.uk
 * @author    Barry Oosthuizen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$row = $tabs = array();
$tabcontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
$row[] = new tabobject('graderreport',
                $CFG->wwwroot . '/grade/report/multigrader/index.php?id=' . $courseid,
                get_string('pluginname', 'gradereport_multigrader'));
if (has_capability('moodle/grade:manage', $tabcontext) ||
        has_capability('moodle/grade:edit', $tabcontext) ||
        has_capability('gradereport/multigrader:view', $tabcontext)) {
    $row[] = new tabobject('preferences',
                    $CFG->wwwroot . '/grade/report/multigrader/preferences.php?id=' . $courseid,
                    get_string('myreportpreferences', 'grades'));
}

$tabs[] = $row;
echo '<div class="gradedisplay">';
print_tabs($tabs, $currenttab);
echo '</div>';

