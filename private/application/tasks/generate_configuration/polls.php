<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto sondę: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano sondę: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono sondę: %s',
	'edit_log_sub' => 'title',
	'table' => 'polls',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_polls_delete',
	'acl_edit' => 'admin_polls_edit',
	'acl_add' => 'admin_polls_add',
	'title' => 'Sondy',
	'title_edit' => 'Edycja sondy',
	'title_add' => 'Dodawanie sondy',
	'grid' => true,
	'acl_grid' => 'admin_polls',
	'acl_grid_multi' => 'admin_polls_multi',
	'fields_create' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł sondy',
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
				'description' => 'Tytuł sondy',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
		),
	),
);