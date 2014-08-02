<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto mecz: %s',
	'delete_log_sub' => 'id',
	'add_log' => 'Dodano mecz: %s',
	'add_log_sub' => 'id',
	'edit_log' => 'Zmieniono mecz: %s',
	'edit_log_sub' => 'id',
	'table' => 'matches',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_matches_delete',
	'acl_edit' => 'admin_matches_edit',
	'acl_add' => 'admin_matches_add',
	'title' => 'Mecze',
	'title_edit' => 'Edycja meczu',
	'title_add' => 'Dodawanie meczu',
	'slug' => 'slug',
	'slug_from' => 'id',
	'grid' => true,
	'acl_grid' => 'admin_matches',
	'acl_grid_multi' => 'admin_matches_multi',
	'fields_create' => array(
		'Rozgrywki' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'W jakich rozgrywkach',
				'type' => 'related',
				'validators' => 'required|exists:competitions,id',
				'related_table' => 'competitions',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'season_id' => array(
				'label' => 'Sezon',
				'description' => 'W jakim sezonie',
				'type' => 'related',
				'validators' => 'required|exists:seasons,id',
				'related_table' => 'seasons',
				'related_key' => 'id',
				'related_value' => 'year',
			),
			'fixture_id' => array(
				'label' => 'Kolejka',
				'description' => 'W której kolejce jest ten mecz. Jeśli nie istnieje zostanie automatycznie utworzona',
				'type' => 'text',
				'validators' => 'required|max:127',
			),
		),
		'Kluby' => array(
			'home_id' => array(
				'label' => 'Gospodarz',
				'description' => 'Drużyna gospodarzy',
				'type' => 'related',
				'validators' => 'required|exists:teams,id',
				'related_table' => 'teams',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'away_id' => array(
				'label' => 'Gość',
				'description' => 'Drużyna gości',
				'type' => 'related',
				'validators' => 'required|exists:teams,id',
				'related_table' => 'teams',
				'related_key' => 'id',
				'related_value' => 'name',
			),
		),
		'Dane' => array(
			'score' => array(
				'label' => 'Wynik',
				'description' => 'Wynik w formacie XX:YY. Zostaw puste jeśli mecz się nie odbył',
				'type' => 'text',
				'validators' => 'match:![0-9]{1,2}[\-\:][0-9]{1,2}!',
			),
			'date' => array(
				'label' => 'Data',
				'description' => 'Data meczu',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
			),
			'stadium' => array(
				'label' => 'Stadion',
				'description' => 'Stadion, na którym odbył/odbędzie się mecz',
				'type' => 'text',
				'validators' => 'required|max:127'
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Krótki opis meczu',
				'type' => 'textarea',
				'validators' => ''
			),
		),
		'Zapowiedź / Raport pomeczowy' => array(
			'prematch_slug' => array(
				'label' => 'Zapowiedź',
				'description' => 'Wybrany news zostanie ustawiony jako zapowiedź tego meczu',
				'type' => 'select',
				'validators' => 'exists:news,slug',
				'options' => array()
			),
			'report_slug' => array(
				'label' => 'Raport pomeczowy',
				'description' => 'Wybrany news zostanie ustawiony jako raport pomeczowy tego meczu',
				'type' => 'select',
				'validators' => 'exists:news,slug',
				'options' => array()
			)
		)
	),
	'fields_edit' => array(
		'Rozgrywki' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'W jakich rozgrywkach',
				'type' => 'related',
				'validators' => 'required|exists:competitions,id',
				'related_table' => 'competitions',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'season_id' => array(
				'label' => 'Sezon',
				'description' => 'W jakim sezonie',
				'type' => 'related',
				'validators' => 'required|exists:seasons,id',
				'related_table' => 'seasons',
				'related_key' => 'id',
				'related_value' => 'year',
			),
			'fixture_id' => array(
				'label' => 'Kolejka',
				'description' => 'W której kolejce jest ten mecz. Jeśli nie istnieje zostanie automatycznie utworzona',
				'type' => 'text',
				'validators' => 'required|max:127',
			),
		),
		'Kluby' => array(
			'home_id' => array(
				'label' => 'Gospodarz',
				'description' => 'Drużyna gospodarzy',
				'type' => 'related',
				'validators' => 'required|exists:teams,id',
				'related_table' => 'teams',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'away_id' => array(
				'label' => 'Gość',
				'description' => 'Drużyna gości',
				'type' => 'related',
				'validators' => 'required|exists:teams,id',
				'related_table' => 'teams',
				'related_key' => 'id',
				'related_value' => 'name',
			),
		),
		'Dane' => array(
			'score' => array(
				'label' => 'Wynik',
				'description' => 'Wynik w formacie XX:YY. Zostaw puste jeśli mecz się nie odbył',
				'type' => 'text',
				'validators' => 'match:![0-9]{1,2}[\-\:][0-9]{1,2}!',
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:255|alpha_dash|unique:matches,slug,(:id)',
				'escape' => true,
			),
			'date' => array(
				'label' => 'Data',
				'description' => 'Data meczu',
				'type' => 'text',
				'validators' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
			),
			'stadium' => array(
				'label' => 'Stadion',
				'description' => 'Stadion, na którym odbył/odbędzie się mecz',
				'type' => 'text',
				'validators' => 'required|max:127'
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Krótki opis meczu',
				'type' => 'textarea',
				'validators' => ''
			),
		),
		'Zapowiedź / Raport pomeczowy' => array(
			'prematch_slug' => array(
				'label' => 'Zapowiedź',
				'description' => 'Wybrany news zostanie ustawiony jako zapowiedź tego meczu',
				'type' => 'select',
				'validators' => 'exists:news,slug',
				'options' => array()
			),
			'report_slug' => array(
				'label' => 'Raport pomeczowy',
				'description' => 'Wybrany news zostanie ustawiony jako raport pomeczowy tego meczu',
				'type' => 'select',
				'validators' => 'exists:news,slug',
				'options' => array()
			)
		)
	),
);