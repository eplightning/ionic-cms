<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto mecz typera: %s',
	'delete_log_sub' => 'home',
	'add_log' => 'Dodano mecz typera: %s',
	'add_log_sub' => 'home',
	'edit_log' => 'Zmieniono mecz typera: %s',
	'edit_log_sub' => 'home',
	'table' => 'bet_matches',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_bet_matches_delete',
	'acl_edit' => 'admin_bet_matches_edit',
	'acl_add' => 'admin_bet_matches_add',
	'title' => 'Typer',
	'title_edit' => 'Edycja meczu',
	'title_add' => 'Dodawanie meczu',
	'grid' => true,
	'acl_grid' => 'admin_bet_matches',
	'acl_grid_multi' => 'admin_bet_matches_multi',
	'fields_create' => array(
		'Kluby' => array(
			'home' => array(
				'label' => 'Gospodarz',
				'description' => 'Gospodarz meczu',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'away' => array(
				'label' => 'Gość',
				'description' => 'Gość meczu',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
		),
		'Inne' => array(
			'score' => array(
				'label' => 'Wynik',
				'description' => 'Wynik w formacie XX:YY. Zostaw puste jeśli mecz się nie odbył',
				'type' => 'text',
				'validators' => 'match:![0-9]{1,2}[\-\:][0-9]{1,2}!',
			),
			'date_start' => array(
				'label' => 'Data rozp.',
				'description' => 'Od kiedy można typować',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
			),
			'date_end' => array(
				'label' => 'Data zak.',
				'description' => 'Do kiedy można typować',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
			),
		),
		'Typer: Obstawianie' => array(
			'ratio_home' => array(
				'label' => 'Przelicznik 1',
				'description' => 'Przelicznik dla wygranej gospodarzy. Dwa miejsca po przecinku',
				'type' => 'text',
				'validators' => 'numeric|min:1'
			),
			'ratio_draw' => array(
				'label' => 'Przelicznik 2',
				'description' => 'Przelicznik dla remisu. Dwa miejsca po przecinku',
				'type' => 'text',
				'validators' => 'numeric|min:1'
			),
			'ratio_away' => array(
				'label' => 'Przelicznik 3',
				'description' => 'Przelicznik dla wygranej gości. Dwa miejsca po przecinku',
				'type' => 'text',
				'validators' => 'numeric|min:1'
			),
		)
	),
	'fields_edit' => array(
		'Kluby' => array(
			'home' => array(
				'label' => 'Gospodarz',
				'description' => 'Gospodarz meczu',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'away' => array(
				'label' => 'Gość',
				'description' => 'Gość meczu',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
		),
		'Inne' => array(
			'score' => array(
				'label' => 'Wynik',
				'description' => 'Wynik w formacie XX:YY. Zostaw puste jeśli mecz się nie odbył',
				'type' => 'text',
				'validators' => 'match:![0-9]{1,2}[\-\:][0-9]{1,2}!',
			),
			'archive' => array(
				'label' => 'Archiwizuj',
				'description' => 'Głównie dla celów łatwiejszego zarządzania meczami w panelu...',
				'type' => 'checkbox'
			),
			'date_start' => array(
				'label' => 'Data rozp.',
				'description' => 'Od kiedy można typować',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
			),
			'date_end' => array(
				'label' => 'Data zak.',
				'description' => 'Do kiedy można typować',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
			),
		),
		'Typer: Obstawianie' => array(
			'ratio_home' => array(
				'label' => 'Przelicznik 1',
				'description' => 'Przelicznik dla wygranej gospodarzy. Dwa miejsca po przecinku',
				'type' => 'text',
				'validators' => 'numeric|min:1',
				'float' => true
			),
			'ratio_draw' => array(
				'label' => 'Przelicznik 2',
				'description' => 'Przelicznik dla remisu. Dwa miejsca po przecinku',
				'type' => 'text',
				'validators' => 'numeric|min:1',
				'float' => true
			),
			'ratio_away' => array(
				'label' => 'Przelicznik 3',
				'description' => 'Przelicznik dla wygranej gości. Dwa miejsca po przecinku',
				'type' => 'text',
				'validators' => 'numeric|min:1',
				'float' => true
			),
		)
	),
);