<?php

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

$formulario = new calendario2_crear_form();


//**********************************************************************************************
//Add a new event
if ($action=="add"){
	if ($formulario->is_cancelled()) {
		$action = "viewevent";
	}
	else if ($fromform = $formulario->get_data()) {
		//Takes the data from the form
		$big_event = new stdClass();
		$big_event->evento = $fromform->evento;
		$big_event->descripcion = $fromform->descripcion;
		$big_event->fechacreacion = time();
		$big_event->fechaevento = $fromform->fecha;
		$big_event->iduser = $userid;
		$insert_action = $DB->insert_record("calendario2_evento", $big_event, $returnid=true, $bulk=false);
		//Inserts the data into the DB
		$status = "Evento agregado satisfactoriamente";
		$action = "viewevent";
		//Let's go see the new event among the rest
	}
}
//End of adding a new event
//**********************************************************************************************
//Edit
if ($action == "edit"){
	if ($edition == null){
		$status =  "No hay nada seleccionado para editar";
		$action = "viewevent";
	}
	else{
		//$edition has the id of the selected event
		$newquery = "SELECT * from mdl_calendario2_evento WHERE iduser =? AND id = $edition";
		if($editar_evento = $DB->get_records_sql($newquery, array ("iduser"=>$userid, "id"=>$edition))){
			//if there is an event with such id and belongs to the user
			$editform = new calendario2_editar_form(null, array("edition"=>$edition,
					"evento"=>$editar_evento[$edition]->evento,
					"descripcion"=>$editar_evento[$edition]->descripcion,
					"fechaevento"=>$editar_evento[$edition]->fechaevento));
			//Not really sure why this works... ¯\_(ツ)_/¯
			$defaultdata = new stdClass();
			$defaultdata->evento = $editar_evento[$edition]->evento;
			$defaultdata->descripcion = $editar_evento[$edition]->descripcion;
			$defaultdata->fechaevento = $editar_evento[$edition]->fechaevento;
			$editform->set_data($defaultdata);
			//Fills the form with the data from the DB
			//NOTE: Fills the date with the current date, not the one from the event
			
			if ($editform->is_cancelled()){
				$action = "viewevent";
			}
			else if($editar = $editform->get_data()){
				$editado = new stdClass();
				$editado->id = $edition;
				$editado->evento = $editar->evento;
				$editado->descripcion = $editar->descripcion;
				$editado->fechaevento = $editar->fecha;
				//Takes the new data and updates it in the DB
				$DB->update_record("calendario2_evento", $editado);
				$action = "viewevent";
				$status = "Evento eitado satisfactoriamente";
			}
		}
	}
}
//End of Edit
//**********************************************************************************************	
//"Delete"
if ($action == "delete"){
	if ($edition == null){
		$status = "No hay nada seleccionado para borrar";
		$action = "viewevent";
	}
	else{
		$deleter = new stdClass();
		$deleter->id = $edition;
		$deleter->fechacreacion = 0;
		
		$DB->update_record("calendario2_evento", $deleter);
		//Update selected event, if fechacreacion is 0, then it won't be shown anymore
		//But it will remain in the DB
		$action = "viewevent";
		$status = "Evento borrado satisfactoriamente";
	}
}
//End "delete"
//**********************************************************************************************
//View events
if ($action == "viewevent"){
	$tabla = new html_table();

	$query = "SELECT * from mdl_calendario2_evento WHERE iduser =? AND fechacreacion != 0 ORDER BY fechaevento";
	//Get all events from user which haven't been "deleted"
	$ndeeventos = $DB->get_records_sql($query, array ("iduser"=>$userid));
	$contadordeeventos = count($ndeeventos);
	$botonurl = new moodle_url("/local/calendario2/index.php", array("action" => "add"));
	if($contadordeeventos>0){
		//If there are events in the DB from this user which have not been deleted...
		foreach($ndeeventos as $ev){
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
			
			
			$tabla->data[] = array(
					$ev->evento,
					$ev->descripcion,
					date("d-m-Y", (float)$ev->fechaevento),
					$editeventiconaction,
					$deleteeventiconaction
			); //Show the retrieved data into a table for viewing
		}
	}
}
//End of view
//**********************************************************************************************

echo $OUTPUT->header();

if ($action == "add"){
	$formulario->display();
}

if ($action == "edit"){
	$editform->display();
}

if ($action == "viewevent"){
	if ($status != null){ 
		print_object($status);
		$status = null;
	}
	echo $OUTPUT->single_button($botonurl,"Agregar Evento");
	echo html_writer::table($tabla);	
}

echo $OUTPUT->footer();