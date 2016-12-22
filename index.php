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
			
			$editform = new calendario2_editar_form(null, array("edition"=>$edition,
					"evento"=>$editar_evento[$edition]->evento,
					"descripcion"=>$editar_evento[$edition]->descripcion,
					"fechaevento"=>$editar_evento[$edition]->fechaevento));
			
			$defaultdata = new stdClass();
			$defaultdata->evento = $editar_evento[$edition]->evento;
			$defaultdata->descripcion = $editar_evento[$edition]->descripcion;
			$defaultdata->fechaevento = $editar_evento[$edition]->fechaevento;
			$editform->set_data($defaultdata);
			
			if ($editform->is_cancelled()){
/*				$cancel = new moodle_url("/local/calendario2/index.php", array(
							"action" => "viewevent"));
				redirect($cancel);*/
				$action = "viewevent";
			}
			else if($editar = $editform->get_data()){
				echo "<br>";
				$editado = new stdClass();
				$editado->id = $edition;
				$editado->evento = $editar->evento;
				$editado->descripcion = $editar->descripcion;
				$editado->fechaevento = $editar->fecha;
				
				$DB->update_record("calendario2_evento", $editado);
				$action = "viewevent";
			}
		}
	}
}
//Fin cosa para editar
//**********************************************************************************************	
//Inicio cosa para borrar

if ($action == "predelete"){
	echo "Esta completa y absolutamente seguro que quiere borrar permanentemente el evento seleccionado?";
	$botondeletey = new moodle_url("/local/calendario2/index.php", array("action" => "delete", "edition"=>$edition));
	$botondeleten = new moodle_url("/local/calendario2/index.php", array("action" => "viewevent"));
	echo $OUTPUT->single_button($botondeletey,"Si");
	echo $OUTPUT->single_button($botondeleten,"No");
}




if ($action == "delete"){
	if ($edition == null){
		echo "No hay nada seleccionado para borrar";
		$action = "viewevent";
	}
	else{
		$deleter = new stdClass();
		$deleter->id = $edition;
		$deleter->fechacreacion = 0;
		
		$DB->update_record("calendario2_evento", $deleter);
		$action = "viewevent";
		echo "Evento borrado satisfactoriamente";
	}


}
//Fin cosa para borrar
//**********************************************************************************************

if ($action == "viewevent"){
	$tabla = new html_table();


	$query = "SELECT * from mdl_calendario2_evento WHERE iduser =? AND fechacreacion != 0";
	$ndeeventos = $DB->get_records_sql($query, array ("iduser"=>$userid));
	$contadordeeventos = count($ndeeventos);
	$botonurl = new moodle_url("/local/calendario2/index.php", array("action" => "add"));
	if($contadordeeventos>0){
		foreach($ndeeventos as $ev){
			$urlevent = new moodle_url("/local/calendario2/index.php", array(
					"action" => "edit",
					"edition" => $ev->id,
			));
			
			$urldelete = new moodle_url("/local/calendario2/index.php", array(
					"action" => "predelete", //aqui cambie la wea
					"edition" => $ev->id,
			));
			
			$editeventicon = new pix_icon("i/edit", "Editar");
			$editeventiconaction = $OUTPUT->action_icon($urlevent, $editeventicon);

			$deleteeventicon = new pix_icon("i/delete", "Borrar");
			$deleteeventiconaction = $OUTPUT->action_icon($urldelete, $deleteeventicon);
			
			
			$tabla->data[] = array(
					$ev->evento,
					$ev->descripcion,
					date("d-m-Y", (float)$ev->fechaevento),
					$editeventiconaction,
					$deleteeventiconaction
			);
		}
	}
}

echo $OUTPUT->header();

if ($action == "add"){
	$formulario->display();
}

if ($action == "edit"){
	$editform->display();
}

if ($action == "viewevent"){
	echo $OUTPUT->single_button($botonurl,"Agregar Evento");
	echo html_writer::table($tabla);	
}

echo $OUTPUT->footer();