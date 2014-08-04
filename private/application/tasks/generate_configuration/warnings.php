<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto ostrzeżenie: %s',
	'delete_log_sub' => 'id',
	'add_log' => 'Dodano ostrzeżenie: %s',
	'add_log_sub' => 'id',
	'edit_log' => 'Zmieniono ostrzeżenie: %s',
	'edit_log_sub' => 'id',
	'table' => 'warnings',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_warnings_delete',
	'acl_edit' => 'admin_warnings_edit',
	'acl_add' => 'admin_warnings_add',
	'title' => 'Ostrzeżenia',
	'title_edit' => 'Edycja ostrzeżenia',
	'title_add' => 'Dodawanie ostrzeżenia',
	'grid' => true,
	'acl_grid' => 'admin_warnings',
	'acl_grid_multi' => 'admin_warnings_multi',
	'fields_create' => array(
		'Dane' => array(
			'user' => array(
				'label' => 'Użytkownik',
				'description' => 'Użytkownik otrzymujący to ostrzeżenie',
				'type' => 'text',
				'validators' => 'required|exists:users,display_name',
			),
			'reason' => array(
				'label' => 'Powód',
				'description' => 'Powód ostrzeżenia',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true
			),
		),
	),
	'fields_edit' => array(
		'Dane' => array(
			'reason' => array(
				'label' => 'Powód',
				'description' => 'Powód ostrzeżenia',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true
			),
		),
	),
);