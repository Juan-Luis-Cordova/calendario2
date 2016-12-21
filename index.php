<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once ($CFG->dirroot . "/local/calendario2/form/form.php");
require_once ($CFG->dirroot . "/local/calendario2/form/editform.php");
global $PAGE, $CFG, $OUTPUT, $DB, $USER;

require_login();
$userid = $USER->id;

$url = new moodle_url('/local/calendario2/index.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
//$PAGE->set_title($title);
//$PAGE->set_heading($title);

$formulario = new calendario2_crear_form();

echo $OUTPUT->header();

$action = optional_param("action", "viewevent", PARAM_TEXT);
$edition = optional_param("id", null, PARAM_INT);

if ($action=="add"){

	if ($formulario->is_cancelled()) {
		$action = "viewevent";
	}
	else if ($fromform = $formulario->get_data()) {
		echo "hay datos <br>";
		
		$big_event = new stdClass();
		$big_event->evento = $fromform->evento;
		$big_event->descripcion = $fromform->descripcion;
		$big_event->fechacreacion = time();
		$big_event->fechaevento = $fromform->fecha;
		$big_event->iduser = $userid;
		//echo $big_event->userid;
		$insert_action = $DB->insert_record("calendario2_evento", $big_event, $returnid=true, $bulk=false);
		$action = "viewevent";
	}
}
//**********************************************************************************************
//cosa para editar
$tabla = new html_table();


$query = "SELECT * from mdl_calendario2_evento WHERE iduser =?";
$ndeeventos = $DB->get_records_sql($query, array ("iduser"=>$userid));
$contadordeeventos = count($ndeeventos);
if($contadordeeventos>0){
	for($i=1; $i<=$contadordeeventos; $i++){
		$urlevent = new moodle_url("/local/calendario2/index.php", array(
				"action" => "edit",
				"edition" => $ndeeventos[$i]->id)); //error: la wea no cambia el valor de edition

		$editeventicon = new pix_icon("i/edit", "Editar");
		$editeventiconaction = $OUTPUT->action_icon($urlevent, $editeventicon);
		
		//Borrar
		//*************************************
		$edition = 1;
		//Prueba para ver qe pasa si lo de arriba funcionara
		echo $edition;
		//*************************************
		//Borrar
		$tabla->data[] = array(
				$ndeeventos[$i]->evento,
				$ndeeventos[$i]->descripcion,
				date("d-m-Y", (float)$ndeeventos[$i]->fechaevento),
				$editeventiconaction
		);
//		echo "hola";

	}
}

if ($action == "edit"){
	
	if ($edition == null){
		//checks if there is something selected to be edited
		echo "No hay nada seleccionado para editar";
		$action = "viewevent";
	}
	else{
		$newquery = "SELECT * from mdl_calendario2_evento WHERE iduser =? AND id = $edition";
		if($editar_evento = $DB->get_records_sql($newquery, array ("iduser"=>$userid, "id"=>$edition))){
						
			$editform = new calendario2_editar_form(null, array("id"=>$edition,
					"evento"=>$editar_evento[1]->evento,
					"descripcion"=>$editar_evento[1]->descripcion,
					"fechaevento"=>$editar_evento[1]->fechaevento));
			
			$editform->display();
			//Error: no rellena los datos; sigue con los sigtes ifs al tiro
			
			
			if ($editform->is_cancelled()){
				$cancel = new moodle_url("/local/calendario2/index.php", array(
							"action" => "viewevent"));
					redirect($cancel);
			}
			else if($editform->get_data()){
				$editado = new stdClass();
				$editado->id = $edition;
				$editado->evento = $editform->get_data()->evento;
				$editado->descripcion = $editform->get_data()->descripcion;
				$editado->fechaevento = $editform->get_data()->fechaevento;
				
				$DB->update_record("calendario2_evento", $editado);
				
				$gobackurl = new moodle_url("/local/calendario2/index.php", array(
						"action" => "viewevent"));
				redirect($gobackurl);
			}
			else{
				echo "Error Fatal!!!";
				$gobackurl = new moodle_url("/local/calendario2/index.php", array(
						"action" => "viewevent"));
				redirect($gobackurl);
			}
		}
		
	}
}
//Fin cosa para editar
//**********************************************************************************************	

if ($action == "viewevent"){
	$time = time();
	//fechaevento > $time AND 
	$sql = "SELECT * from mdl_calendario2_evento WHERE iduser =?";
	$eventos = $DB->get_records_sql($sql, array ("iduser"=>$userid));
	$contador = count($eventos);
	//echo $contador;
	//echo $eventos[1]->evento;
	$string = "";
	$botonurl = new moodle_url("/local/calendario2/index.php", array("action" => "add"));
	for ($i=1; $i<=$contador; $i++){
		$string.= $eventos[$i]->evento;
		$string.= "<br>";
		$string.= $eventos[$i]->descripcion;
		$string.= "<br>";
		$string.= date("d-m-Y", (float)$eventos[$i]->fechaevento);
		$string.= "<br><br><br>";
		
	}
	
	
}

if ($action == "add"){
	$formulario->display();
}

if ($action == "edit"){
	//Error: no llega a este edit para llenar el formulario
	$editform->display();
}

if ($action == "viewevent"){
	echo $OUTPUT->single_button($botonurl,"Agregar Evento");
	echo html_writer::table($tabla);
	//echo $string;
	
}

echo $OUTPUT->footer();