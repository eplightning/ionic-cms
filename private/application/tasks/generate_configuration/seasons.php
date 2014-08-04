<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto sezon: %s',
	'delete_log_sub' => 'year',
	'add_log' => 'Dodano sezon: %s',
	'add_log_sub' => 'year',
	'edit_log' => 'Zmieniono sezon: %s',
	'edit_log_sub' => 'year',
	'table' => 'seasons',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_seasons_delete',
	'acl_edit' => 'admin_seasons_edit',
	'acl_add' => 'admin_seasons_add',
	'title' => 'Sezony',
	'title_edit' => 'Edycja sezonu',
	'title_add' => 'Dodawanie sezonu',
	'grid' => true,
	'acl_grid' => 'admin_seasons',
	'acl_grid_multi' => 'admin_seasons',
	'fields_create' => array(
		'Dane' => array(
			'year' => array(
				'label' => 'Rok sezonu',
				'description' => 'Pierwszy rok sezonu (dla 2012/2013 podaj 2012 itd.)',
				'type' => 'text',
				'validators' => 'integer|min:1800|max:2100|unique:seasons,year',
				'int' => true
			),
		),
	),
	'fields_edit' => array(
		'Dane' => array(
			'year' => array(
				'label' => 'Rok sezonu',
				'description' => 'Pierwszy rok sezonu (dla 2012/2013 podaj 2012 itd.)',
				'type' => 'text',
				'validators' => 'integer|min:1800|max:2100|unique:seasons,year,(:id)',
				'int' => true
			),
		),
	),
);