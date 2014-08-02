<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto grupę: %s',
	'delete_log_sub' => 'name',
	'add_log' => 'Dodano grupę: %s',
	'add_log_sub' => 'name',
	'edit_log' => 'Zmieniono grupę: %s',
	'edit_log_sub' => 'name',
	'table' => 'groups',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_groups_delete',
	'acl_edit' => 'admin_groups_edit',
	'acl_add' => 'admin_groups_add',
	'title' => 'Grupy',
	'title_edit' => 'Edycja grupy',
	'title_add' => 'Dodawanie grupy',
	'grid' => true,
	'acl_grid' => 'admin_groups',
	'acl_grid_multi' => 'admin_groups_multi',
	'fields_create' => array(
		'Dane' => array(
			'name' => array(
				'label' => 'Nazwa',
				'description' => 'Nazwa grupy',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Opis grupy',
				'type' => 'textarea',
				'validators' => 'required|max:255',
				'escape' => true
			),
		),
	),
	'fields_edit' => array(
		'Dane' => array(
			'name' => array(
				'label' => 'Nazwa',
				'description' => 'Nazwa grupy',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Opis grupy',
				'type' => 'textarea',
				'validators' => 'required|max:255',
				'escape' => true
			),
		),
	),
);