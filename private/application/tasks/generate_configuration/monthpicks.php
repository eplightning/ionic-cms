<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto głosowanie: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano głosowanie: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono głosowanie: %s',
	'edit_log_sub' => 'title',
	'table' => 'monthpicks',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_monthpicks_delete',
	'acl_edit' => 'admin_monthpicks_edit',
	'acl_add' => 'admin_monthpicks_add',
	'title' => 'Piłkarz miesiąca',
	'title_edit' => 'Edycja głosowania',
	'title_add' => 'Dodawanie głosowania',
	'grid' => true,
	'acl_grid' => 'admin_monthpicks',
	'acl_grid_multi' => 'admin_monthpicks_multi',
	'fields_create' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł głosowania',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
		),
	),
	'fields_edit' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł głosowania',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
		),
	),
);