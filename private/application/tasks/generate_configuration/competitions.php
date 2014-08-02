<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto rozgrywki: %s',
	'delete_log_sub' => 'name',
	'add_log' => 'Dodano rozgrywki: %s',
	'add_log_sub' => 'name',
	'edit_log' => 'Zmieniono rozgrywki: %s',
	'edit_log_sub' => 'name',
	'table' => 'competitions',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_competitions_delete',
	'acl_edit' => 'admin_competitions_edit',
	'acl_add' => 'admin_competitions_add',
	'title' => 'Rozgrywki',
	'title_edit' => 'Edycja rozgrywek',
	'title_add' => 'Dodawanie rozgrywek',
	'slug' => 'slug',
	'slug_from' => 'name',
	'grid' => true,
	'acl_grid' => 'admin_competitions',
	'acl_grid_multi' => 'admin_competitions',
	'fields_create' => array(
		'Dane' => array(
			'name' => array(
				'label' => 'Nazwa',
				'description' => 'Nazwa rozgrywek',
				'type' => 'text',
				'validators' => 'required|max:127|unique:competitions,name',
				'escape' => true,
			),
		),
	),
	'fields_edit' => array(
		'Dane' => array(
			'name' => array(
				'label' => 'Nazwa',
				'description' => 'Nazwa rozgrywek',
				'type' => 'text',
				'validators' => 'required|max:127|unique:competitions,name,(:id)',
				'escape' => true,
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:competitions,slug,(:id)',
				'escape' => true,
			),
		),
	),
);