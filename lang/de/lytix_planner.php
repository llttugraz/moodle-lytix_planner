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
 * @author     Viktoria Wieser
 * @copyright  2020 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lytix Planer';

$string['privacy:metadata'] = 'This plugin does not store any data.';

// Planer.
$string['Planner'] = 'Planer';
$string['Milestone'] = 'Meilenstein';
$string['Lecture'] = 'Vorlesung';
$string['Exam'] = 'Prüfung';
$string['Quiz'] = 'Quiz';
$string['Assignment'] = 'Aufgabe';
$string['Feedback'] = 'Feedback';
$string['Interview'] = 'Abgabegespräch';
$string['Other'] = 'Sonstiges';
$string['add_milestone'] = 'Neuer Meilenstein';
$string['add_event'] = 'Neues Ereignis';
$string['event'] = 'Ereignis';
$string['event_completed'] = 'Ereignis abgeschlossen?';
$string['open_settings'] = 'Einstellungen';
$string['set_date'] = 'Wählen Sie ein Datum aus:';
$string['set_date_help'] = 'Wählen Sie ein Datum als Deadline für diese Aufgabe aus.';
$string['set_startdate'] = 'Wählen Sie ein Datum aus:';
$string['set_startdate_help'] = 'Wählen Sie ein Datum als Deadline für diese Aufgabe aus.';
$string['due_date'] = 'Datum: ';
$string['type_lecture'] = 'Vorlesung';
$string['type_exam'] = 'Prüfung';
$string['type_quiz'] = 'Quiz';
$string['type_assignment'] = 'Übung';
$string['type_feedback'] = 'Feedback';
$string['type_interview'] = 'Abgabegespräch';
$string['type_other'] = 'Sonstiges';
$string['set_type'] = 'Wählen Sie einen typ:';
$string['set_type_help'] = 'Wählen Sie einen der Kategorien aus, sollte ihnen eine fehlen, wenden Sie sich bitte an das Support-Team';
$string['set_select_group'] = 'Wählen Sie eine Gruppe';
$string['set_select_group_help'] = 'Wählen Sie eine Gruppe aus den Vorschlägen aus um dieses Event für alle Mitglieder in der Gruppe zu erstellen.
                                    \"keine Gruppe\" erstellt das Event für alle Teilnehmer.';
$string['no_group'] = 'keine Gruppe';
$string['set_title'] = 'Schreiben Sie einen Titel:';
$string['set_title_help'] = 'Schreiben Sie einen Titel für diesen Meilenstein in die dafür vorgesehene Box.';
$string['set_text'] = 'Schreiben Sie einen Text:';
$string['set_text_help'] = 'Schreiben Sie eine etwas detailliertere Beschreibung des Meilensteins in die dafür vorgesehene Box.';
$string['set_visible'] = 'Ist dieses Ereignis sichtbar?';
$string['set_visible_help'] = 'Soll dieses Ereignis für die Studierenden sichtbar sein?';
$string['set_mandatory'] = 'Ist dieses Ereignis verpflichtend?';
$string['set_mandatory_help'] = 'Soll dieses Ereignis für die Studierenden verpflichtet sein?';
$string['set_graded'] = 'Wird dieses Ereignis benotet?';
$string['set_graded_help'] = 'Wird dieses Ereignis für die Studierenden benotet werden?';
$string['set_points'] = 'Erreichte Punkte:';
$string['set_points_help'] = 'Wenn dieses Ereignis benotet wird, dann tragen Sie hier die erreichten Punkte ein.';
$string['get_send'] = 'Benachrichtigung versendet?';
$string['get_send_help'] = 'Wenn aktiviert, wurde eine Benachrichtiung zur beendigung dieser Aktivität versand.';
$string['set_delete'] = 'Diesen Meilenstein löschen?';
$string['set_delete_help'] = 'Sind Sie sicher, dass Sie diesen Meilenstein löschen möchten? Sie können den Vorgang nicht rückgängig machen!';
$string['mandatory'] = '<div class="alert alert-warning">Dieses Ereignis ist verpflichtend!!!</div>';
$string['set_completed'] = 'Wurde diese Aufgabe abgeschlossen?';
$string['set_completed_help'] = 'Wurde diese Aufgabe abgeschlossen? Wurden alle Punkte erledigt?';
$string['set_new_type'] = 'Neuen Eventtypen hinzufügen';
$string['set_new_type_help'] = 'Möchten Sie einen Eventtyp hinzufügen?';
$string['set_select_other_german'] = 'Eventtyp in deutsch:';
$string['set_select_other_german_help'] = 'Wie soll das neue Event in deutsch erscheinen?';
$string['set_select_other_english'] = 'Eventtyp in englisch:';
$string['set_select_other_english_help'] = 'Wie soll das neue Event in englisch erscheinen?';
$string['set_delete_type'] = 'Möchten Sie den Eventtyp löschen?';
$string['set_hour'] = 'Wählen Sie die Stunde für die Endzeit: ';
$string['set_minute'] = 'Wählen Sie die Minute für die Endzeit:';
$string['set_endtime'] = 'Endzeit:';
$string['set_endtime_help'] = 'Wählen Sie die Endzeit (Stunde und Minute) für dieses Ereignis aus.';
$string['set_room'] = 'Raum:';
$string['set_room_help'] = 'Schreiben Sie die Raumbezeichnung in das Feld oder fügen Sie den Link zum Raum ein.';
$string['set_gradeitem'] = 'Punkte:';
$string['set_gradeitem_help'] = 'Verknüpfen Sie ein ausgewähltes Grade-Element mit diesem Event.';
$string['costum_settings'] = 'Benutzerdefinierte Kurseinstellungen';
$string['connect_gradebook'] = 'Keine Verknüpfung zum Gradebook herstellen.';
$string['countcompleted'] = 'Abgeschlossen:';
$string['countcompleted_help'] = 'Zeigt wie viele Studierende das Event bereits abgeschlossen haben.';

// Legend.
$string['legend'] = '<b>verpflichtende</b> Ereignisse haben eine schwarze Kontur, <b>benotete</b> Ereignisse sind mit <b>*</b> markiert';

// Modal warning.
$string['title_required'] = 'Ein Titel ist erforderlich.';
$string['type_exists'] = '<div class="alert alert-danger">Der Eventtyp existiert bereits.</div>';
$string['type_required'] = '<div class="alert alert-danger">Ein Eventtyp in Englisch und Deutsch ist notwendig.</div>';
$string['type_not_deleteable'] = '<div class="alert alert-danger">Der Eventtyp kann nicht gelöscht werden, da ein Event mit diesem Typ bereits exisitert.
                                                                    Löschen Sie das Event bevor Sie den Eventtypen löschen.</div>';
$string['event_limit'] = '<div class="alert alert-danger">Limit an Events für diesen Tag bereits erreicht.</div>';
$string['time_smaller'] = '<div class="alert alert-danger">Endzeit ist kleiner als Startzeit!</div>';
$string['timeoutofrange'] = '<div class="alert alert-danger">Die ausgewählte Zeit liegt nicht im Zeitbereich des Kurses.</div>';

// Planer - Settings.
$string['enable_course_notifications'] = 'Erlaube Benachrichtigungen';
$string['enable_course_notifications_help'] = 'Erlaube Benachrichtigungen an Studierende für diesen Kurs';
$string['enable_user_customization'] = 'Erlaube personalisierte Benachrichtigungen';
$string['enable_user_customization_help'] = 'Wenn aktiv, können die Studierenden selbst die Einstellungen für die Benachrichtigungen konfigurieren.';
$string['start_time'] = 'Wählen Sie ein Startdatum aus:';
$string['start_time_help'] = 'Wählen Sie ein Datum aus. Ab diesem Datum sind dann Benachrichtigungen für die Studierenden aktiv.';
$string['end_time'] = 'Wählen Sie ein Enddatum aus:';
$string['end_time_help'] = 'Wählen Sie ein Datum aus. Ab diesem Datum sind dann Benachrichtigungen für die Studierenden inaktiv.';
$string['softlock'] = 'Erlaube Änderungen';
$string['softlock_help'] = 'Sicherheits Mechanismus um nicht unabsichtlich Änderungen vorzunehmen.';
$string['offset'] = 'Wählen Sie einen Offset (in Tagen) aus:';
$string['offset_help'] = 'Wählen Sie ein Offset aus. Die Benachrichtigung wird dann X Tage vor der Deadline versendet.';
$string['enable_custom_customization'] = "Personalisierte Benachrichtigungen einstellen";
$string['enable_custom_customization_help'] = "Wollen Sie Ihre Benachrichtigungen personalisieren?";
$string['notification_option'] = 'Sendeoption';
$string['notification_option_help'] = 'Wählen Sie bitte eine Sendeoption aus. Dann werden alle Notifikationen für diesen Typ entsprechend versendet.';
$string['email'] = 'E-Mail';
$string['message'] = 'Nachricht';
$string['both'] = 'E-Mail und Nachricht';
$string['none'] = 'keine Benachrichtigungen';

// Mails and Messages.
$string['messageprovider:notification_message'] = 'Benachrichtigungsmeldung über Ereignisse und Meilensteine des Planers.';
// Planner deadline.
$string['deadline_subject'] = '[Learners Corner - Kurs {$a}] Die Deadline für das folgende Timeline-Ereignis naht.';
$string['deadline_text'] =
    'Liebe*r Studierende*r , die Deadline für den Meilenstein / die Meilensteine : {$a->subjects} naht.

Wir möchten nicht, dass du die Deadline verpasst! Folge dem Link:(<a href="{$a->courseurl}">{$a->course}</a>) für weitere Informationen.
Falls du nur vergessen hast, den Status des Meilensteins / der Meilensteine in den Planer einzutragen,
kannst du das jetzt nachholen, um deine Einträge aktuell zu halten!<br>';
// Monthly report.
$string['report_subject'] = '[Learners Corner - Course {$a}] Dein monatlicher Planer-Bericht.';
// Report Case all completed.
$string['report_text_all_completed'] =
    'Gratulation! Du hast bis jetzt alle Meilensteine für (<a href="{$a->courseurl}">{$a->course}</a>) erreicht!<br>';
// Report Case mandatory completed.
$string['report_text_mandatory_completed'] =
    'Gut gemacht! Du hast alle verpflichtende Meilensteine für (<a href="{$a->courseurl}">{$a->course}</a>) erreicht.<br>';
// Report Case some completed.
$string['report_text_not_completed'] =
    'Anscheinend hast du  ein paar verpflichtende Meilensteine für (<a href="{$a->courseurl}">{$a->course}</a>) nicht erreicht.
Du hast ({$a->complete}/{$a->shouldcomplete}) Meilensteine erreicht. Wenn das der Fall ist, gib dein Bestes,
um an den Kursinhalten dranzubleiben und suche das Gespräch  mit deinen Vortragenden.
Hier ist eine Liste der verpassten Meilensteine {$a->missedevents}<br>';
// Footer.
$string['footer'] = '<br>Projekt: Learning Analytics – Students in Focus<br>
TU Graz Institute of Interactive Systems and Data Science<br>
TU Graz Lehr- und Lerntechnologien<br>
Email: <a href="mailto:learners.corner@tugraz.at">learners.corner@tugraz.at</a><br>
Website: <a href="https://learning-analytics.at">https://learning-analytics.at</a><br>
<img width="200" height="200" alt="Logo" src="data:image/png;base64,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/ZJnrNwwC3m2TaA3s1CKm1pjMEzl3EVcUlrITAkhKKfWGFxQwGct/8D694djV7g3rvD/80iH1cPtdPf93RDvMNmQ7hqUcWAtsgMppaw5G0aj+z8/HNN+IO3czDf/MM1whza0qe4NC/wi7JL5T14izd0wlqofhmyrG+Humu1smXzxh7uKdm71Jzen6iwiBFYOimLDzBle6rOyy7zMu3F71urVssbvzXTTANMILHVF8axhqMe8G+i7hNBcS3w1jdhd2qOWElhhIY94hosXzN7QH4Gl7HPJqmnh0i5j1uyipFVVLhsQcRtS0wIV5T5nbfC27ZBdSivLnCHB/jtQyuopr0b3zxfwEP56vQwlmKpqYOnETSZd7ihem/2d+fYWP4VMmCnBX2T7Zpu4x7k91uu9/cNxuax9CEA7a0HXEPEX/o/R7zv+3FJ6kPv2pL39w9suje4tATvu0HP9uKITaZ8lxAYvn8Aqih2z5t2Q3fW4IGwY+Dtyv4Se5lXu4iEyIRBuzIBlv78fG/bRqcoajvE+CtayL1jqflKX0S81LUF/m1rt9d/T3v7hXTTE6KE627Mn/aDlVCWk28Jn9w8X8WTSadxauKP1XadjPHZg2muanPQc2tP7buGcq4Vdu0fY/z/p4tBDToHVNKXHUNw9VBcnk74rHZ+FAb/va95yH5asr1xNOTy3Gd5TF1zvw6wFVXf5yuXw7+vuAoZzjM8vLl3F29ctBBsvuX9rSjldAvc79+vNgLbjlucZd3FgTqweismkao4yQUUxXnCnwFVz8lAN7FiyahLarh6qPX2rL1E7UJc2H6BSToG1Gf5qDamkdRs+81nHBnZAUj6BVfocXEtbIWVBxvMsSQEq8gssANmi4ygAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgAZBBYAGQQWABkEFgANzrn/A4lEPqK3oQn/AAAAAElFTkSuQmCC">';
// Privacy.
$string['privacy:metadata:lytix_planner_milestone'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_milestone:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_milestone:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_milestone:type'] = "Typ";
$string['privacy:metadata:lytix_planner_milestone:marker'] = "Marker";
$string['privacy:metadata:lytix_planner_milestone:startdate'] = "Stardatum";
$string['privacy:metadata:lytix_planner_milestone:enddate'] = "Enddatum";
$string['privacy:metadata:lytix_planner_milestone:title'] = "Titel";
$string['privacy:metadata:lytix_planner_milestone:text'] = "Text";
$string['privacy:metadata:lytix_planner_milestone:moffset'] = "Offset";
$string['privacy:metadata:lytix_planner_milestone:moption'] = "Option";
$string['privacy:metadata:lytix_planner_milestone:completed'] = "Beendet";
$string['privacy:metadata:lytix_planner_milestone:send'] = "Gesendet";

$string['privacy:metadata:lytix_planner_event_comp'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_event_comp:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_event_comp:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_event_comp:eventid'] = "Eventid";
$string['privacy:metadata:lytix_planner_event_comp:completed'] = "Beendet";
$string['privacy:metadata:lytix_planner_event_comp:send'] = "Gesendet";
$string['privacy:metadata:lytix_planner_event_comp:timestamp'] = "Zeitstempel";

$string['privacy:metadata:lytix_planner_usr_settings'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_usr_settings:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_usr_settings:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_usr_settings:enable_custom_customization'] = "Bearbeitung aktivieren";
$string['privacy:metadata:lytix_planner_usr_settings:types'] = "Typen";

$string['privacy:metadata:lytix_planner_usr_grade_rep'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_planner_usr_grade_rep:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_planner_usr_grade_rep:courseid'] = "The course ID will be saved for knowing to which \course
 the data belongs to";
$string['privacy:metadata:lytix_planner_usr_grade_rep:quizpoints'] = "Quizpunkte";
$string['privacy:metadata:lytix_planner_usr_grade_rep:assingpoints'] = "Aufgabenpunkte";
$string['privacy:metadata:lytix_planner_usr_grade_rep:totalpoints'] = "Toatalpunkte";
$string['privacy:metadata:lytix_planner_usr_grade_rep:maxpoints'] = "Maximale Punkte";
$string['privacy:metadata:lytix_planner_usr_grade_rep:lastmodified'] = "zuletzt geändert";
