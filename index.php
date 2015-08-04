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
 * The gradebook multi grader report
 *
 * @package   gradereport_multigrader
 * @copyright 2012 onwards Barry Oosthuizen http://elearningstudio.co.uk
 * @author    Barry Oosthuizen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/multigrader/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/grade/report/multigrader/categorylib.php');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('checkboxtree', 'gradereport_multigrader');
$PAGE->requires->css('/grade/report/multigrader/checkboxtree/css/checkboxtree.css');

// end of insert

$courseid = required_param('id', PARAM_INT);        // course id
$page = optional_param('page', 0, PARAM_INT);   // active page
$edit = optional_param('edit', -1, PARAM_BOOL); // sticky editting mode

$sortitemid = optional_param('sortitemid', 0, PARAM_ALPHANUM); // sort by which grade item
$action = optional_param('action', 0, PARAM_ALPHAEXT);
$target = optional_param('target', 0, PARAM_ALPHANUM);
$toggle = optional_param('toggle', NULL, PARAM_INT);
$toggle_type = optional_param('toggle_type', 0, PARAM_ALPHANUM);

// Multi grader form.
$formsubmitted = optional_param('formsubmitted', 0, PARAM_TEXT);
// End of multi grader form.

$PAGE->set_url(new moodle_url('/grade/report/multigrader/index.php', array('id' => $courseid)));

// Basic access checks.
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}
require_login($course);
$context = context_course::instance($course->id);

require_capability('gradereport/multigrader:view', $context);
require_capability('moodle/grade:viewall', $context);

// Return tracking object.
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'multigrader', 'courseid' => $courseid, 'page' => $page));

// Last selected report session tracking.
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'multigrader';

// Handle toggle change request.
if (!is_null($toggle) && !empty($toggle_type)) {
    set_user_preferences(array('grade_report_show' . $toggle_type => $toggle));
}

// First make sure we have proper final grades - this must be done before constructing of the grade tree.
grade_regrade_final_grades($courseid);

// Perform actions.
if (!empty($target) && !empty($action) && confirm_sesskey()) {
    grade_report_grader::do_process_action($target, $action);
}
$reportname = get_string('pluginname', 'gradereport_multigrader');

// Print header
print_grade_page_head($COURSE->id, 'report', 'multigrader', $reportname, false);
?>
<script type="text/javascript">

    jQuery(document).ready(function(){
        jQuery("#docheckchildren").checkboxTree({
            collapsedarrow: "checkboxtree/images/checkboxtree/img-arrow-collapsed.gif",
            expandedarrow: "checkboxtree/images/checkboxtree/img-arrow-expanded.gif",
            blankarrow: "checkboxtree/images/checkboxtree/img-arrow-blank.gif",
            checkchildren: true,
            checkparents: false
        });

    });

</script>

<?php

echo '<br/><br/>';

echo '<form method="post" action="index.php">';
echo '<div id="categorylist">';
echo '<ul class="unorderedlisttree" id="docheckchildren">';
gradereport_multigrader_print_category();

echo '</ul>';
echo '<div><input type="hidden" name="id" value="' . $courseid . '"/></div>';
echo '<div><input type="hidden" name="userid" value="' . $USER->id . '"/></div>';
echo '<div><input type="hidden" name="formsubmitted" value="Yes"/></div>';
echo '<div><input type="hidden" name="sesskey" value="' . sesskey() . '"/></div>';

echo '<div><input type="submit" name="submitquery" value="' . get_string("submit") . '"/></div>';
echo '</div>';
echo '</form>';
echo '<br/><br/>';
// multi grader form test

if ($formsubmitted === "Yes") {

    $coursebox = optional_param_array('coursebox', 0, PARAM_RAW);

    $selectedcourses = array();

    if (!empty($coursebox)) {

        foreach ($coursebox as $id => $value) {
            $selectedcourses[] = $value;
        }
    }

    if (!empty($selectedcourses)) {

        list($courselist, $params) = $DB->get_in_or_equal($selectedcourses, SQL_PARAMS_NAMED, 'm');
        $sql = "select * FROM {course} WHERE id $courselist ORDER BY shortname";
        $courses = $DB->get_records_sql($sql, $params);

        foreach ($courses as $thiscourse) {
            $courseid = $thiscourse->id;
            $context = context_course::instance($courseid);
            if (has_capability('moodle/grade:viewall', $context)) {
                if (has_capability('gradereport/multigrader:view', $context)) {

                    echo '<br/><br/><hr/>';
                    echo html_writer::tag('p', '<b><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $thiscourse->id . '">' . $thiscourse->shortname . '</a></b>');
                    echo '<br/>';
                    $exportxlsurl = new moodle_url('/grade/export/xls/index.php', array('id' => $thiscourse->id));
                    $xlsicon = html_writer::img($CFG->wwwroot . '/grade/report/multigrader/pix/excel.gif',
                            get_string('xls:view', 'gradeexport_xls'));

                    echo html_writer::div(html_writer::link($exportxlsurl, $xlsicon), 'export_padding');
                    $exportodsurl = new moodle_url('/grade/export/ods/index.php', array('id' => $thiscourse->id));
                    $odsicon = html_writer::img($CFG->wwwroot . '/grade/report/multigrader/pix/ods.gif',
                            get_string('ods:view', 'gradeexport_ods'));

                    echo html_writer::div(html_writer::link($exportodsurl, $odsicon), 'export_padding');
                    $exportxmlurl = new moodle_url('/grade/export/xml/index.php', array('id' => $thiscourse->id));
                    $xmlicon = html_writer::img($CFG->wwwroot . '/grade/report/multigrader/pix/xml.gif',
                            get_string('xml:view', 'gradeexport_xml'));

                    echo html_writer::div(html_writer::link($exportxmlurl, $xmlicon), 'export_padding');
                    $exporttxturl = new moodle_url('/grade/export/txt/index.php', array('id' => $thiscourse->id));
                    $txticon = html_writer::img($CFG->wwwroot . '/grade/report/multigrader/pix/text.gif',
                            get_string('txt:view', 'gradeexport_txt'));

                    echo html_writer::div(html_writer::link($exporttxturl, $txticon), 'export_padding');
                    $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'multigrader', 'courseid' => $courseid, 'page' => $page));
                    // Basic access checks.
                    $conditions = array("id" => $thiscourse->id);
                    if (!$course = $DB->get_record('course', $conditions)) {
                        print_error('nocourseid');
                    }

                    $context = context_course::instance($thiscourse->id);

                    // Initialise the multi grader report object that produces the table
                    // The class grade_report_grader_ajax was removed as part of MDL-21562.
                    $report = new grade_report_multigrader($courseid, $gpr, $context, $page, $sortitemid);

                    // Processing posted grades & feedback here.
                    if ($data = data_submitted() and confirm_sesskey() and has_capability('moodle/grade:edit', $context)) {
                        $warnings = $report->process_data($data);
                    } else {
                        $warnings = array();
                    }
                    // Final grades MUST be loaded after the processing.
                    $report->load_users();
                    $numusers = $report->get_numusers();
                    $report->load_final_grades();

                    echo '<div class="clearer"></div>';
                    // Show warnings if any.
                    foreach ($warnings as $warning) {
                        echo $OUTPUT->notification($warning);
                    }

                    $studentsperpage = $report->get_students_per_page();
                    // Don't use paging if studentsperpage is empty or 0 at course AND site levels.
                    if (!empty($studentsperpage)) {
                        echo $OUTPUT->paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
                    }

                    $reporthtml = $report->get_grade_table();

                    // Print submit button.
                    echo $reporthtml;

                    // Prints paging bar at bottom for large pages.
                    if (!empty($studentsperpage) && $studentsperpage >= 20) {
                        echo $OUTPUT->paging_bar($numusers, $report->page, $studentsperpage, $report->pbarurl);
                    }
                }
            }
        }
    }
}
echo $OUTPUT->footer();
