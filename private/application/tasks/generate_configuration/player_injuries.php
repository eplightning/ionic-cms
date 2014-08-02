<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto kontuzję: %s',
	'delete_log_sub' => 'injury',
	'add_log' => 'Dodano kontuzję: %s',
	'add_log_sub' => 'injury',
	'edit_log' => 'Zmieniono kontuzję: %s',
	'edit_log_sub' => 'injury',
	'table' => 'player_injuries',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_player_injuries_delete',
	'acl_edit' => 'admin_player_injuries_edit',
	'acl_add' => 'admin_player_injuries_add',
	'title' => 'Kontuzje',
	'title_edit' => 'Edycja kontuzji',
	'title_add' => 'Dodawanie kontuzji',
	'grid' => true,
	'acl_grid' => 'admin_player_injuries',
	'acl_grid_multi' => 'admin_player_injuries',
	'fields_create' => array(
		'Dane' => array(
			'player_id' => array(
				'label' => 'Zawodnik',
				'description' => 'Imię i nazwisko zawodnika. Musi już istnieć w bazie danych',
				'type' => 'text',
				'validators' => 'required|max:127|exists:players,name',
			),
			'injury' => array(
				'label' => 'Krótki opis',
				'description' => 'Krótki opis kontuzji',
				'type' => 'text',
				'validators' => 'required|max:127',
			),
			'recovery_date' => array(
				'label' => 'Data wygaśnięcia',
				'description' => 'Data powrotu zawodnika do zdrowia. Zostaw puste jeśli nieznana',
				'type' => 'text',
				'validators' => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!'
			)
		),
	),
	'fields_edit' => array(
		'Dane' => array(
			'player_id' => array(
				'label' => 'Zawodnik',
				'description' => 'Imię i nazwisko zawodnika. Musi już istnieć w bazie danych',
				'type' => 'text',
				'validators' => 'required|max:127|exists:players,name',
			),
			'injury' => array(
				'label' => 'Krótki opis',
				'description' => 'Krótki opis kontuzji',
				'type' => 'text',
				'validators' => 'required|max:127',
			),
			'recovery_date' => array(
				'label' => 'Data wygaśnięcia',
				'description' => 'Data powrotu zawodnika do zdrowia. Zostaw puste jeśli nieznana',
				'type' => 'text',
				'validators' => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!'
			)
		),
	),
);