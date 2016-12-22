<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once ($CFG->dirroot . "/local/calendario2/form/form.php");
require_once ($CFG->dirroot . "/local/calendario2/form/editform.php");
global $PAGE, $CFG, $OUTPUT, $DB, $USER;

$action = optional_param("action", "viewevent", PARAM_TEXT);
$edition = optional_param("edition", null, PARAM_INT);

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
if ($action == "edit"){
	
	if ($edition == null){
		//checks if there is something selected to be edited
		echo "No hay nada seleccionado para editar";
		$action = "viewevent";
	}
	else{
		$newquery = "SELECT * from mdl_calendario2_evento WHERE iduser =? AND id = $edition";
		if($editar_evento = $DB->get_records_sql($newquery, array ("iduser"=>$userid, "id"=>$edition))){
			
//			echo count($editar_evento);  No entiendo por qe funciona, pero funciona...
			
			$editform = new calendario2_editar_form(null, array("id"=>$edition,
					"evento"=>$editar_evento[$edition]->evento,
					"descripcion"=>$editar_evento[$edition]->descripcion,
					"fechaevento"=>$editar_evento[$edition]->fechaevento));
			
			$defaultdata = new stdClass();
			$defaultdata->evento = $editar_evento[$edition]->evento;
			$defaultdata->descripcion = $editar_evento[$edition]->descripcion;
			$defaultdata->fechaevento = $editar_evento[$edition]->fechaevento;
			$editform->set_data($defaultdata);
			
			echo "jojojo";
			if ($editform->is_cancelled()){
/*				$cancel = new moodle_url("/local/calendario2/index.php", array(
							"action" => "viewevent"));
				redirect($cancel);*/
				$action = "viewevent";
			}
			else if($editar = $editform->get_data()){
				echo "lala";
				$editado = new stdClass();
				$editado->id = $edition;
				$editado->evento = $editar->get_data()->evento;
				$editado->descripcion = $editar->get_data()->descripcion;
				$editado->fechaevento = $editar->get_data()->fechaevento;
				
				$DB->update_record("calendario2_evento", $editado);
				$action = "viewevent";
				//$gobackurl = new moodle_url("/local/calendario2/index.php", array(
				//		"action" => "viewevent"));
				//redirect($gobackurl);
			}
			echo "cueck";
//			else{
//				echo "Error Fatal!!!";
//				$gobackurl = new moodle_url("/local/calendario2/index.php", array(
//						"action" => "viewevent"));
//				redirect($gobackurl);
//			}
		}
	}
}
//Fin cosa para editar
//**********************************************************************************************	

if ($action == "viewevent"){
	$tabla = new html_table();


	$query = "SELECT * from mdl_calendario2_evento WHERE iduser =?";
	$ndeeventos = $DB->get_records_sql($query, array ("iduser"=>$userid));
	$contadordeeventos = count($ndeeventos);

	if($contadordeeventos>0){
		foreach($ndeeventos as $ev){
			$urlevent = new moodle_url("/local/calendario2/index.php", array(
					"action" => "edit",
					"edition" => $ev->id,
			));
			$editeventicon = new pix_icon("i/edit", "Editar");
			$editeventiconaction = $OUTPUT->action_icon($urlevent, $editeventicon);

			$botonurl = new moodle_url("/local/calendario2/index.php", array("action" => "add"));
			$tabla->data[] = array(
					$ev->evento,
					$ev->descripcion,
					date("d-m-Y", (float)$ev->fechaevento),
					$editeventiconaction
			);
		}
	}
}

if ($action == "add"){
	$formulario->display();
}

if ($action == "edit"){
	//Error: no llega a este edit para llenar el formulario
	echo "wololo";
	$editform->display();
}

if ($action == "viewevent"){
	echo $OUTPUT->single_button($botonurl,"Agregar Evento");
	echo html_writer::table($tabla);	
}

echo $OUTPUT->footer();