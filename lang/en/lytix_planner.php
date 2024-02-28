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
 * Activity plugin for lytix
 *
 * @package    lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lytix Planner';

$string['privacy:metadata'] = 'This plugin does not store any data.';

// Task.
$string['cron_send_planner_notifications'] = 'Send Planner Notifications Task for subplugin lytix_planner';
$string['cron_send_planner_report_notifications'] = 'Send planner report notification task for subplugin lytix_planner';

// Planner.

$string['Planner'] = 'Planner';
$string['description_teacher'] = 'Overview of all course events. Adjust the zoom level to display specific timeframes.';
$string['description_student'] = 'Overview of all course events and milestones you have added. Adjust the zoom level to display specific timeframes.';
$string['Milestone'] = 'Milestone(s)';
$string['Lecture'] = 'Lecture(s)';
$string['Exam'] = 'Exam(s)';
$string['Quiz'] = 'Quiz(zes)';
$string['Assignment'] = 'Assignment(s)';
$string['Feedback'] = 'Feedback(s)';
$string['Interview'] = 'Student’s interview(s)';
$string['Other'] = 'Other Event(s)';

$string['add_milestone'] = 'Add Milestone';
$string['add_event'] = 'Add Event';
$string['event'] = 'Event';
$string['event_completed'] = 'Event completed?';
$string['serial_event'] = 'Create serial event';
$string['open_settings'] = 'Settings';
$string['set_date'] = 'Event date:';
$string['set_date_help'] = 'Set a date and time for the start of this event. Can be the same as end date to only enter a deadline.';
$string['set_startdate'] = 'Event date:';
$string['set_startdate_help'] = 'Set a date and time for the start of this event. Can be the same as end date to only enter a deadline.';
$string['due_date'] = 'Event date: ';
$string['type_lecture'] = 'Lecture';
$string['type_quiz'] = 'Quiz';
$string['type_assignment'] = 'Assignment';
$string['type_feedback'] = 'Feedback';
$string['type_exam'] = 'Exam';
$string['type_interview'] = 'Student’s interview';
$string['type_other'] = 'Other';
$string['set_type'] = 'Event type:';
$string['set_type_help'] = 'Select one of these types for your event. If these types do not fit your goal,
                             please contact our support team.';

$string['set_select_group'] = 'Select a group';
$string['set_select_group_help'] = 'Select a group to create a group event. “no group” creates an event for all participants.';
$string['no_group'] = 'no group';
$string['set_title'] = 'Event title:';
$string['set_title_help'] = 'Write a title for this event in the box provided.';
$string['set_text'] = 'Event description:';
$string['set_text_help'] = 'Write a short text describing this event in the box provided.';
$string['set_visible'] = 'Event visible for students?';
$string['set_visible_help'] = 'Should this event be visible for the students?';
$string['set_mandatory'] = 'Event compulsory for students?';
$string['set_mandatory_help'] = 'Should this event be mandatory for the students?';
$string['set_graded'] = 'Will this event be graded?';
$string['set_graded_help'] = 'Will this event be graded for students?';
$string['set_points'] = 'Points achieved:';
$string['set_points_help'] = 'If this event is graded, enter the points achieved.';
$string['get_send'] = 'Notification send?';
$string['get_send_help'] = 'If active, a notification was send to the participants.';
$string['set_delete'] = 'Delete this milestone?';
$string['set_delete_help'] = 'Are you sure you want to delete this milestone?  You can not undo this.';
$string['mandatory'] = '<div class="alert alert-warning">This event is mandatory!!!</div>';
$string['set_completed'] = 'Have you completed this event?';
$string['set_completed_help'] = 'Check this box if you did all required tasks.';
$string['set_new_type'] = 'Add new event type';
$string['set_new_type_help'] = 'Do you want to add an event type?';
$string['set_select_other_german'] = 'Event type in German:';
$string['set_select_other_german_help'] = 'How should the new event appear in German?';
$string['set_select_other_english'] = 'Event type in English:';
$string['set_select_other_english_help'] = 'How should the new event appear in English?';
$string['set_delete_type'] = 'Delete event type?';
$string['set_hour'] = 'Select the hour of the end time: ';
$string['set_minute'] = 'Select the minute of the end time:';
$string['set_endtime'] = 'End time:';
$string['set_endtime_help'] = 'Set an end time for this event. Can be the same as start date to only enter a deadline';
$string['set_room'] = 'Room:';
$string['set_room_help'] = 'Write the room name in the field or paste the link to the room.';
$string['set_gradeitem'] = 'Points:';
$string['set_gradeitem_help'] = 'Link a selected grade element to this event.';
$string['costum_settings'] = 'Custom course settings';
$string['connect_gradebook'] = 'Do not create a link to the gradebook';
$string['countcompleted'] = 'Completion:';
$string['completed_by'] = 'Completed by';
$string['students'] = 'students';
$string['countcompleted_help'] = 'Shows how many students have already completed the event.';

// Legend.
$string['legend'] = '<b>graded</b> events are underlined, <b>mandatory</b> events are marked with <b>*</b>';

// Different views.
$string['view'] = 'Zoom level';
$string['month'] = 'MONTH';
$string['months'] = 'MONTHS';
$string['next'] = 'Next month';
$string['previous_month'] = 'Previous month';
$string['options'] = 'Options';
$string['calendarweek'] = 'CW';

// Modal warning.
$string['title_required'] = 'A title is required.';
$string['type_exists'] = '<div class="alert alert-danger">The event type already exists.</div>';
$string['type_required'] = '<div class="alert alert-danger">An event type in English and German is required.</div>';
$string['type_not_deleteable'] = '<div class="alert alert-danger">The event type cannot be deleted because an event with this type already exists.
                                                                  Delete the event before deleting the event type.</div>';
$string['event_limit'] = '<div class="alert alert-danger">Limit for events on this day already reached.</div>';
$string['time_smaller'] = '<div class="alert alert-danger">End time is smaller than start time!</div>';
$string['timeoutofrange'] = '<div class="alert alert-danger">The selected time is not in the time range of the course.</div>';

// Planner - Settings.
$string['enable_course_notifications'] = 'Enable Notifications';
$string['enable_course_notifications_help'] = 'Enable notifications to the students of this course.';
$string['enable_user_customization'] = 'Allow personalized Notifications';
$string['enable_user_customization_help'] = 'Once activ, the students can configure their settings for the notification.';
$string['start_time'] = 'Start date:';
$string['start_time_help'] = 'Select a startdate. Once this day is reached, the students receive notifications';
$string['end_time'] = 'End date:';
$string['end_time_help'] = 'Select a enddate. Once this day is reached, the students no longer receive notifications';
$string['softlock']  = 'Unlock settings';
$string['softlock_help'] = 'Tick box to change settings. Prevents accidental changes.';
$string['offset'] = 'Select Offset(Days):';
$string['offset_help'] = 'Select a offset. The students will receive notifications X days before the deadline.';
$string['enable_custom_customization'] = "Configure Personal Notifications";
$string['enable_custom_customization_help'] = "If active, you can edit the settings blow.";
$string['enable_lecture_notifications'] = 'Enable Lecture Notifications';
$string['enable_lecture_notifications_help'] = 'Enable lecture notifications to the students of this course.';
$string['enable_quiz_notifications'] = 'Enable Quiz Notifications';
$string['enable_quiz_notifications_help'] = 'Enable quiz notifications to the students of this course.';
$string['enable_ass_notifications'] = 'Enable Assignment Notifications';
$string['enable_ass_notifications_help'] = 'Enable assignment notifications to the students of this course.';
$string['enable_feedback_notifications'] = 'Enable Feedback Notifications';
$string['enable_feedback_notifications_help'] = 'Enable feedback notifications to the students of this course.';
$string['enable_exam_notifications'] = 'Enable Exam Notifications';
$string['enable_exam_notifications_help'] = 'Enable exam notifications to the students of this course.';
$string['enable_interview_notifications'] = 'Enable Interview Notifications';
$string['enable_interview_notifications_help'] = 'Enable interview notifications to the students of this course.';
$string['enable_other_notifications'] = 'Enable Other Notifications';
$string['enable_other_notifications_help'] = 'Enable other notifications to the students of this course.';
$string['error_text'] = '<div class="alert alert-danger">Something went wrong, please reload the page(F5). <br>
 If this error happens again please contact your administrator.</div>';
$string['loading_msg'] = "Loading data from the system, please wait";
$string['notification_option'] = 'Send option';
$string['notification_option_help'] = 'Please choose a sending option. All events of this type will be send out accordingly.';
$string['email'] = 'E-Mail';
$string['message'] = 'Message';
$string['both'] = 'E-Mail and Message';
$string['none'] = 'no Notifications';

// Mails and Messages.
$string['messageprovider:notification_message'] = 'Notification message about events and milestones of the planner.';
// Planner deadlines.
$string['deadline_subject'] = '[Learners Corner - Course {$a}] Deadline(s) approaching';
$string['deadline_text'] =
    'Dear student, The following deadline(s) milestones are approaching: {$a->subjects} We do not want you to miss any deadline,
     please follow the link to visit the course (<a href="{$a->courseurl}">{$a->course}</a>) and check what is to come.
     If you have already done the task(s), change the milestone status right away in the (<a href="Learners Corner - Planner">{$a->course}</a>).<br>';
// Monthly report.
$string['report_subject'] = '[Learners Corner - Course {$a}] Dein monatlicher Planer-Bericht.';
// Report Case all completed.
$string['report_text_all_completed'] =
    'Congratulations! You have reached all the course milestones for (<a href="{$a->courseurl}">{$a->course}</a>) up to now!<br>';
// Report Case mandatory completed.
$string['report_text_mandatory_completed'] =
    'Good job! You have reached all the mandatory milestones for the course (<a href="{$a->courseurl}">{$a->course}</a>).<br>';
// Report Case some completed.
$string['report_text_not_completed'] =
    'It seems that you missed some mandatory milestones for the course (<a href="{$a->courseurl}">{$a->course}</a>).<br>
If this is the case, do your best to keep up with the course work, and make sure to talk to your teacher.
Milestones completed: ({$a->complete}/{$a->shouldcomplete}) Missed milestones: {$a->missedevents}
If you have already done the task(s), change the milestone status right away in the (<a href="Learners Corner - Planner">{$a->course}</a>).<br>';
// Footer.
$string['footer'] = '<br>Project: Learning Analytics – Students in Focus<br>
TU Graz Institute of Interactive Systems and Data Science<br>
TU Graz Lehr- und Lerntechnologien<br>
Email: <a href="mailto:learners.corner@tugraz.at">learners.corner@tugraz.at</a><br>
Website: <a href="https://learning-analytics.at">https://learning-analytics.at</a>
<img width="200" height="200" alt="Logo" src="data:image/png;base64,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/ZJnrNwwC3m2TaA3s1CKm1pjMEzl3EVcUlrITAkhKKfWGFxQwGct/8D694djV7g3rvD/80iH1cPtdPf93RDvMNmQ7hqUcWAtsgMppaw5G0aj+z8/HNN+IO3czDf/MM1whza0qe4NC/wi7JL5T14izd0wlqofhmyrG+Humu1smXzxh7uKdm71Jzen6iwiBFYOimLDzBle6rOyy7zMu3F71urVssbvzXTTANMILHVF8axhqMe8G+i7hNBcS3w1jdhd2qOWElhhIY94hosXzN7QH4Gl7HPJqmnh0i5j1uyipFVVLhsQcRtS0wIV5T5nbfC27ZBdSivLnCHB/jtQyuopr0b3zxfwEP56vQwlmKpqYOnETSZd7ihem/2d+fYWP4VMmCnBX2T7Zpu4x7k91uu9/cNxuax9CEA7a0HXEPEX/o/R7zv+3FJ6kPv2pL39w9suje4tATvu0HP9uKITaZ8lxAYvn8Aqih2z5t2Q3fW4IGwY+Dtyv4Se5lXu4iEyIRBuzIBlv78fG/bRqcoajvE+CtayL1jqflKX0S81LUF/m1rt9d/T3v7hXTTE6KE627Mn/aDlVCWk28Jn9w8X8WTSadxauKP1XadjPHZg2muanPQc2tP7buGcq4Vdu0fY/z/p4tBDToHVNKXHUNw9VBcnk74rHZ+FAb/va95yH5asr1xNOTy3Gd5TF1zvw6wFVXf5yuXw7+vuAoZzjM8vLl3F29ctBBsvuX9rSjldAvc79+vNgLbjlucZd3FgTqweismkao4yQUUxXnCnwFVz8lAN7FiyahLarh6qPX2rL1E7UJc2H6BSToG1Gf5qDamkdRs+81nHBnZAUj6BVfocXEtbIWVBxvMsSQEq8gssANmi4ygAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgANzrn/A4lEPqK3oQn/AAAAAElFTkSuQmCC">';
// Privacy.
$string['privacy:metadata:lytix_planner_milestone'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_milestone:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_milestone:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_milestone:type'] = "Type";
$string['privacy:metadata:lytix_planner_milestone:marker'] = "Marker";
$string['privacy:metadata:lytix_planner_milestone:startdate'] = "Startdate";
$string['privacy:metadata:lytix_planner_milestone:enddate'] = "Enddate";
$string['privacy:metadata:lytix_planner_milestone:title'] = "Title";
$string['privacy:metadata:lytix_planner_milestone:text'] = "Text";
$string['privacy:metadata:lytix_planner_milestone:moffset'] = "Offset";
$string['privacy:metadata:lytix_planner_milestone:moption'] = "Option";
$string['privacy:metadata:lytix_planner_milestone:completed'] = "Completed";
$string['privacy:metadata:lytix_planner_milestone:send'] = "Send";

$string['privacy:metadata:lytix_planner_event_comp'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_event_comp:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_event_comp:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_event_comp:eventid'] = "Eventid";
$string['privacy:metadata:lytix_planner_event_comp:completed'] = "Completed";
$string['privacy:metadata:lytix_planner_event_comp:send'] = "Send";
$string['privacy:metadata:lytix_planner_event_comp:timestamp'] = "Timestamp";

$string['privacy:metadata:lytix_planner_usr_settings'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_usr_settings:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_usr_settings:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_usr_settings:enable_custom_customization'] = "Enable custom customization";
$string['privacy:metadata:lytix_planner_usr_settings:types'] = "Types";

$string['privacy:metadata:lytix_planner_usr_grade_rep'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_usr_grade_rep:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_usr_grade_rep:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_usr_grade_rep:quizpoints'] = "Quizpoints";
$string['privacy:metadata:lytix_planner_usr_grade_rep:assingpoints'] = "Assignmentpoints";
$string['privacy:metadata:lytix_planner_usr_grade_rep:totalpoints'] = "Totalpoints";
$string['privacy:metadata:lytix_planner_usr_grade_rep:maxpoints'] = "Maximal points";
$string['privacy:metadata:lytix_planner_usr_grade_rep:lastmodified'] = "Last modified";
