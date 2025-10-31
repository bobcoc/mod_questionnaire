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
 * Manage personalized files for questionnaire
 *
 * @package mod_questionnaire
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/questionnaire/questionnaire.class.php');
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.
$action = optional_param('action', 'view', PARAM_ALPHA);

if (!$cm = get_coursemodule_from_id('questionnaire', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", ["id" => $cm->course])) {
    throw new moodle_exception('coursemisconf');
}

if (!$questionnaire = $DB->get_record("questionnaire", ["id" => $cm->instance])) {
    throw new moodle_exception('invalidcoursemodule');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_capability('mod/questionnaire:manage', $context);

$PAGE->set_url('/mod/questionnaire/personalfiles.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($questionnaire->name));
$PAGE->set_heading(format_string($course->fullname));

// Handle file upload.
if ($action == 'upload' && confirm_sesskey()) {
    require_once($CFG->dirroot.'/lib/filelib.php');
    
    $draftitemid = file_get_submitted_draft_itemid('personalfiles');
    
    if ($draftitemid) {
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        
        // Get all files from draft area.
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'itemid, filepath, filename', false);
        
        $imported = 0;
        $errors = [];
        
        foreach ($draftfiles as $file) {
            $filename = $file->get_filename();
            
            // Extract student ID from filename (before extension).
            $pathinfo = pathinfo($filename);
            $idnumber = $pathinfo['filename'];
            
            // Find user by idnumber.
            if ($user = $DB->get_record('user', ['idnumber' => $idnumber])) {
                // Check if user is enrolled in course.
                if (is_enrolled($context, $user->id)) {
                    // Check if record already exists.
                    $existing = $DB->get_record('questionnaire_personal_file', [
                        'questionnaireid' => $questionnaire->id,
                        'userid' => $user->id
                    ]);
                    
                    $record = new stdClass();
                    $record->questionnaireid = $questionnaire->id;
                    $record->userid = $user->id;
                    $record->idnumber = $idnumber;
                    $record->filename = $filename;
                    $record->filearea = 'personalfile';
                    $record->timemodified = time();
                    
                    if ($existing) {
                        // Delete old file.
                        $fs->delete_area_files($context->id, 'mod_questionnaire', 'personalfile', $existing->id);
                        
                        // Update record.
                        $record->id = $existing->id;
                        $DB->update_record('questionnaire_personal_file', $record);
                        $itemid = $existing->id;
                    } else {
                        // Create new record.
                        $record->timecreated = time();
                        $itemid = $DB->insert_record('questionnaire_personal_file', $record);
                    }
                    
                    // Save file to proper location.
                    $filerecord = [
                        'contextid' => $context->id,
                        'component' => 'mod_questionnaire',
                        'filearea' => 'personalfile',
                        'itemid' => $itemid,
                        'filepath' => '/',
                        'filename' => $filename
                    ];
                    
                    $fs->create_file_from_storedfile($filerecord, $file);
                    $imported++;
                } else {
                    $errors[] = get_string('personalfile_usernotenrolled', 'questionnaire', $idnumber);
                }
            } else {
                $errors[] = get_string('personalfile_usernotfound', 'questionnaire', $idnumber);
            }
        }
        
        // Clean up draft files.
        file_clear_draft_area($draftitemid);
        
        if ($imported > 0) {
            \core\notification::success(get_string('personalfile_imported', 'questionnaire', $imported));
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                \core\notification::error($error);
            }
        }
    }
    
    redirect(new moodle_url('/mod/questionnaire/personalfiles.php', ['id' => $cm->id]));
}

// Handle file deletion.
if ($action == 'delete' && confirm_sesskey()) {
    $fileid = required_param('fileid', PARAM_INT);
    
    if ($record = $DB->get_record('questionnaire_personal_file', ['id' => $fileid, 'questionnaireid' => $questionnaire->id])) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_questionnaire', 'personalfile', $fileid);
        $DB->delete_records('questionnaire_personal_file', ['id' => $fileid]);
        
        \core\notification::success(get_string('personalfile_deleted', 'questionnaire'));
    }
    
    redirect(new moodle_url('/mod/questionnaire/personalfiles.php', ['id' => $cm->id]));
}

echo $OUTPUT->header();

// Display upload form.
echo html_writer::start_tag('div', ['class' => 'personalfiles-upload']);
echo html_writer::tag('h3', get_string('personalfile_upload', 'questionnaire'));
echo html_writer::tag('p', get_string('personalfile_uploadhelp', 'questionnaire'));

echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'action' => new moodle_url('/mod/questionnaire/personalfiles.php', ['id' => $cm->id, 'action' => 'upload'])
]);

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$draftitemid = file_get_submitted_draft_itemid('personalfiles');
file_prepare_draft_area($draftitemid, $context->id, 'mod_questionnaire', 'personalfile_temp', 0);

$options = [
    'maxbytes' => $course->maxbytes,
    'maxfiles' => 50,
    'accepted_types' => ['image']
];

echo $OUTPUT->file_picker('personalfiles', $draftitemid, $options);

echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('upload'), 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

echo html_writer::empty_tag('hr');

// Display existing files.
echo html_writer::tag('h3', get_string('personalfile_existing', 'questionnaire'));

$files = $DB->get_records('questionnaire_personal_file', ['questionnaireid' => $questionnaire->id], 'idnumber');

if (empty($files)) {
    echo html_writer::tag('p', get_string('personalfile_nofiles', 'questionnaire'));
} else {
    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('idnumber'));
    echo html_writer::tag('th', get_string('fullname'));
    echo html_writer::tag('th', get_string('filename', 'questionnaire'));
    echo html_writer::tag('th', get_string('timeuploaded', 'questionnaire'));
    echo html_writer::tag('th', get_string('actions'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($files as $file) {
        $user = $DB->get_record('user', ['id' => $file->userid]);
        
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $file->idnumber);
        echo html_writer::tag('td', fullname($user));
        echo html_writer::tag('td', $file->filename);
        echo html_writer::tag('td', userdate($file->timemodified));
        
        $deleteurl = new moodle_url('/mod/questionnaire/personalfiles.php', [
            'id' => $cm->id,
            'action' => 'delete',
            'fileid' => $file->id,
            'sesskey' => sesskey()
        ]);
        
        echo html_writer::tag('td', html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-sm btn-danger']));
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo $OUTPUT->footer();
