<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto statystyki: %s',
	'delete_log_sub' => 'id',
	'add_log' => 'Dodano statystyki: %s',
	'add_log_sub' => 'id',
	'edit_log' => 'Zmieniono statystyki: %s',
	'edit_log_sub' => 'title',
	'table' => 'player_stats',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_player_stats_delete',
	'acl_edit' => 'admin_player_stats_edit',
	'acl_add' => 'admin_player_stats_add',
	'title' => 'Statystyki',
	'title_edit' => 'Edycja statystyk',
	'title_add' => 'Dodawanie statystyk',
	'grid' => true,
	'acl_grid' => 'admin_player_stats',
	'acl_grid_multi' => 'admin_player_stats_multi',
	'fields_create' => array(
		'Zawodnik' => array(
			'player_id' => array(
				'label' => 'Zawodnik',
				'description' => 'Imię i nazwisko zawodnika. Jeśli nie istnieje zostanie utworzony (i przypisany do klubu wybranego poniżej)',
				'type' => 'text',
				'validators' => 'required|max:127',
			),
			'team_id' => array(
				'label' => 'Klub',
				'description' => 'Wymagany tylko w sytuacji, gdy podany powyżej zawodnik nie istnieje',
				'type' => 'related',
				'validators' => 'exists:teams,id',
				'related_table' => 'teams',
				'related_key' => 'id',
				'related_value' => 'name',
			),
		),
		'Rozgrywki' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'Rozgrywki których dotyczą te statystyki',
				'type' => 'related',
				'validators' => 'required|exists:competitions,id',
				'related_table' => 'competitions',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'season_id' => array(
				'label' => 'Sezon',
				'description' => 'Sezon którego dotyczą te statystyki',
				'type' => 'related',
				'validators' => 'required|exists:seasons,id',
				'related_table' => 'seasons',
				'related_key' => 'id',
				'related_value' => 'year',
			),
		),
		'Statystyki' => array(
			'goals' => array(
				'label' => 'Bramek',
				'description' => 'Ilość zdobytych goli',
				'type' => 'text',
				'validators' => 'required|integer',
			),
			'yellow_cards' => array(
				'label' => 'Żółtych kartek',
				'description' => 'Ilość otrzymanych żółtych kartek przez tego zawodnika',
				'type' => 'text',
				'validators' => 'required|integer',
			),
			'red_cards' => array(
				'label' => 'Czerwonych kartek',
				'description' => 'Ilość otrzymanych czerwonych kartek przez tego zawodnika',
				'type' => 'text',
				'validators' => 'required|integer',
			),
		)
	),
	'fields_edit' => array(
		'Rozgrywki' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'Rozgrywki których dotyczą te statystyki',
				'type' => 'related',
				'validators' => 'required|exists:competitions,id',
				'related_table' => 'competitions',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'season_id' => array(
				'label' => 'Sezon',
				'description' => 'Sezon którego dotyczą te statystyki',
				'type' => 'related',
				'validators' => 'required|exists:seasons,id',
				'related_table' => 'seasons',
				'related_key' => 'id',
				'related_value' => 'year',
			),
		),
		'Statystyki' => array(
			'goals' => array(
				'label' => 'Bramek',
				'description' => 'Ilość zdobytych goli',
				'type' => 'text',
				'validators' => 'required|integer',
			),
			'yellow_cards' => array(
				'label' => 'Żółtych kartek',
				'description' => 'Ilość otrzymanych żółtych kartek przez tego zawodnika',
				'type' => 'text',
				'validators' => 'required|integer',
			),
			'red_cards' => array(
				'label' => 'Czerwonych kartek',
				'description' => 'Ilość otrzymanych czerwonych kartek przez tego zawodnika',
				'type' => 'text',
				'validators' => 'required|integer',
			),
		)
	),
);