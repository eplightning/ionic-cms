<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto transfer: %s',
	'delete_log_sub' => 'id',
	'add_log' => 'Dodano transfer: %s',
	'add_log_sub' => 'id',
	'edit_log' => 'Zmieniono transfer: %s',
	'edit_log_sub' => 'id',
	'table' => 'player_transfers',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_player_transfers_delete',
	'acl_edit' => 'admin_player_transfers_edit',
	'acl_add' => 'admin_player_transfers_add',
	'title' => 'Transfery',
	'title_edit' => 'Edycja transferu',
	'title_add' => 'Dodawanie transferu',
	'grid' => true,
	'acl_grid' => 'admin_player_transfers',
	'acl_grid_multi' => 'admin_player_transfers_multi',
	'fields_create' => array(
		'Podstawowe' => array(
			'player_id' => array(
				'label' => 'Zawodnik',
				'description' => 'Imię i nazwisko zawodnika. Jeśli nie istnieje zostanie utworzony',
				'type' => 'text',
				'validators' => 'required|max:127',
			),
			'from_team' => array(
				'label' => 'Od',
				'description' => 'Jeśli klub nie istnieje to zostanie utworzony',
				'type' => 'text',
				'validators' => 'required|max:127'
			),
			'team_id' => array(
				'label' => 'Do',
				'description' => 'Jeśli klub nie istnieje to zostanie utworzony',
				'type' => 'text',
				'validators' => 'required|max:127'
			),
			'date' => array(
				'label' => 'Data',
				'description' => 'Data wykonania transferu',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!'
			)
		),
		'Opcjonalne' => array(
			'type' => array(
				'label' => 'Rodzaj transferu',
				'description' => 'Rodzaj tego transferu',
				'type' => 'select',
				'options' => array(
					0 => 'Zwykły',
					1 => 'Wypożyczenie',
					2 => 'Powrót z wypożyczenia'
				),
				'validators' => 'integer|min:0|max:2'
			),
			'cost' => array(
				'label' => 'Koszt',
				'description' => 'Koszt transakcji. Dowolna waluta',
				'type' => 'text',
				'validators' => 'max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Informacje o transferze',
				'type' => 'editor',
				'validators' => '',
			),
		)
	),
	'fields_edit' => array(
		'Podstawowe' => array(
			'date' => array(
				'label' => 'Data',
				'description' => 'Data wykonania transferu',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!'
			)
		),
		'Opcjonalne' => array(
			'type' => array(
				'label' => 'Rodzaj transferu',
				'description' => 'Rodzaj tego transferu',
				'type' => 'select',
				'options' => array(
					0 => 'Zwykły',
					1 => 'Wypożyczenie',
					2 => 'Powrót z wypożyczenia'
				),
				'validators' => 'integer|min:0|max:2'
			),
			'cost' => array(
				'label' => 'Koszt',
				'description' => 'Koszt transakcji. Dowolna waluta',
				'type' => 'text',
				'validators' => 'max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Informacje o transferze',
				'type' => 'editor',
				'validators' => '',
			),
		)
	),
);