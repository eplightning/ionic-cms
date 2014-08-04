<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto kolejkę: %s',
	'delete_log_sub' => 'name',
	'add_log' => 'Dodano kolejkę: %s',
	'add_log_sub' => 'name',
	'edit_log' => 'Zmieniono kolejkę: %s',
	'edit_log_sub' => 'name',
	'table' => 'fixtures',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_fixtures_delete',
	'acl_edit' => 'admin_fixtures_edit',
	'acl_add' => 'admin_fixtures_add',
	'title' => 'Kolejki',
	'title_edit' => 'Edycja kolejki',
	'title_add' => 'Dodawanie kolejki',
	'grid' => true,
	'acl_grid' => 'admin_fixtures',
	'acl_grid_multi' => 'admin_fixtures_multi',
	'fields_create' => array(
		'Dane' => array(
			'name' => array(
				'label' => 'Nazwa',
				'description' => 'Nazwa kolejki',
				'type' => 'text',
				'validators' => 'required',
				'escape' => true,
			),
			'number' => array(
				'label' => 'Numer',
				'description' => 'Numer kolejki używany podczas sortowania. System spróbuje wygenerować automatycznie jeśli pole jest puste.',
				'type' => 'text',
				'validators' => 'integer|max:255',
			),
		),
		'Powiązane' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'Kolejka jakich rozgrywek',
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
		)
	),
	'fields_edit' => array(
		'Dane' => array(
			'name' => array(
				'label' => 'Nazwa',
				'description' => 'Nazwa kolejki',
				'type' => 'text',
				'validators' => 'required',
				'escape' => true,
			),
			'number' => array(
				'label' => 'Numer',
				'description' => 'Numer kolejki używany podczas sortowania. System spróbuje wygenerować automatycznie jeśli pole jest puste.',
				'type' => 'text',
				'validators' => 'integer|max:255',
			),
		),
		'Powiązane' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'Kolejka jakich rozgrywek',
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
		)
	),
);