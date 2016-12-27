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
/*
 * @package    local
 * @subpackage calendario
 * @copyright  2016 Javier Gonzalez <javiergonzalez@alumnos.uai.cl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once ($CFG->dirroot . "/local/calendario2/form/form.php");
require_once ($CFG->dirroot . "/local/calendario2/form/editform.php");
global $PAGE, $CFG, $OUTPUT, $DB, $USER;

$action = optional_param("action", "viewevent", PARAM_TEXT);
$status = optional_param("status", null, PARAM_TEXT);
$edition = optional_param("edition", null, PARAM_INT);

require_login();
$userid = $USER->id;

$url = new moodle_url('/local/calendario2/index.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Calendario');


//Add a new event
if ($action == "add"){
	$addform = new calendario2_add_form();
	if ($addform->is_cancelled()) {
		$action = "viewevent";
	}
	else if ($fromform = $addform->get_data()) {
		//Takes the data from the form
		$createdevent = new stdClass();
		$createdevent->event = $fromform->event;
		$createdevent->description = $fromform->description;
		$createdevent->creationdate = time();
		$createdevent->eventdate = $fromform->date;
		$createdevent->iduser = $userid;

		$insertaction = $DB->insert_record("calendario2_evento", $createdevent, $returnid=true, $bulk=false);
		//Inserts the data into the DB
		$status = "Evento agregado satisfactoriamente";
		$action = "viewevent";
		//Let's go see the new event among the rest
	}
}
//End of adding a new event

//Edit
if ($action == "edit"){
	if ($edition == null){
		$status =  "No hay nada seleccionado para editar";
		$action = "viewevent";
	}
	else{
		//$edition has the id of the selected event
		$newquery = "SELECT * FROM {calendario2_evento} 
		WHERE iduser =? 
		AND id = ?";
		if($editevent = $DB->get_records_sql($newquery, array ("iduser"=>$userid, "id"=>$edition))){
			//if there is an event with such id and belongs to the user
			$editform = new calendario2_edit_form(null, array("edition"=>$edition,
					"event"=>$editevent[$edition]->event,
					"description"=>$editevent[$edition]->description,
					"eventdate"=>$editevent[$edition]->eventdate));
			$defaultdata = new stdClass();
			$defaultdata->event = $editevent[$edition]->event;
			$defaultdata->description = $editevent[$edition]->description;
			$defaultdata->eventdate = $editevent[$edition]->eventdate;
			$editform->set_data($defaultdata);
			//Fills the form with the data from the DB
			//NOTE: Fills the date with the current date, not the one from the event
			
			if ($editform->is_cancelled()){
				$action = "viewevent";
			}
			else if($edit = $editform->get_data()){
				$edited = new stdClass();
				$edited->id = $edition;
				$edited->event = $edit->event;
				$edited->description = $edit->description;
				$edited->eventdate = $edit->date;
				//Takes the new data and updates it in the DB
				$DB->update_record("calendario2_evento", $edited);
				$action = "viewevent";
				$status = "Evento editado satisfactoriamente";
			}
		}
	}
}
//End of Edit
	
//"Delete"
if ($action == "delete"){
	if ($edition == null){
		$status = "No hay nada seleccionado para borrar";
		$action = "viewevent";
	}
	else{
		$deleter = new stdClass();
		$deleter->id = $edition;
		$deleter->creationdate = 0;
		
		$DB->update_record("calendario2_evento", $deleter);
		//Update selected event, if creationdate is 0, then it won't be shown anymore
		//But it will remain in the DB
		$action = "viewevent";
		$status = "Evento borrado satisfactoriamente";
	}
}
//End "delete"

//View events
if ($action == "viewevent"){
	$table = new html_table();

	$query = "SELECT * FROM {calendario2_evento} 
			WHERE iduser =? 
			AND creationdate != 0 
			ORDER BY eventdate";
	//Creationdate == 0 means it was "deleted"
	//Get all events from user which haven't been "deleted"
	$getevents = $DB->get_records_sql($query, array ("iduser"=>$userid));
	$eventcounter = count($getevents);
	$botonurl = new moodle_url("/local/calendario2/index.php", array("action" => "add"));
	if($eventcounter == 0){
		$status = "Ud no tiene eventos";
	}
	if($eventcounter>0){
		//If there are events in the DB from this user which have not been deleted...
		$table->head = array("Evento", "Descripción del evento", "Fecha del evento", "Editar", "Borrar");
		foreach($getevents as $ev){
			//Add a button for each event for editing or deleting
			$urlevent = new moodle_url("/local/calendario2/index.php", array(
					"action" => "edit",
					"edition" => $ev->id,
			));
			
			$urldelete = new moodle_url("/local/calendario2/index.php", array(
					"action" => "delete",
					"edition" => $ev->id,
			));
			
			$editeventicon = new pix_icon("i/edit", "Editar");
			$editeventiconaction = $OUTPUT->action_icon($urlevent, $editeventicon);

			$deleteeventicon = new pix_icon("t/delete", "Borrar");
			$deleteeventiconaction = $OUTPUT->action_icon($urldelete, $deleteeventicon,
					new confirm_action("Esta completamente seguro de que quiere borrar este evento?"));
			
			
			$table->data[] = array(
					$ev->event,
					$ev->description,
					date("d-m-Y", (float)$ev->eventdate),
					$editeventiconaction,
					$deleteeventiconaction
			); //Show the retrieved data into a table for viewing
		}
	}
}
//End of view


echo $OUTPUT->header();

if ($action == "add"){
	$addform->display();
}

if ($action == "edit"){
	$editform->display();
}

if ($action == "viewevent"){
	if ($status != null){ 
		p($status, $strip=false);
		$status = null;
	}
	echo $OUTPUT->single_button($botonurl,"Agregar Evento");
	echo html_writer::table($table);	
}

echo $OUTPUT->footer();