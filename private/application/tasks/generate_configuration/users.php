<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto użytkownika: %s',
	'delete_log_sub' => 'display_name',
	'add_log' => 'Dodano użytkownika: %s',
	'add_log_sub' => 'display_name',
	'edit_log' => 'Zmieniono użytkownika: %s',
	'edit_log_sub' => 'display_name',
	'table' => 'users',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_users_delete',
	'acl_edit' => 'admin_users_edit',
	'acl_add' => 'admin_users_add',
	'title' => 'Użytkownicy',
	'title_edit' => 'Edycja użytkownika',
	'title_add' => 'Dodawanie użytkownika',
	'slug' => 'slug',
	'slug_from' => 'display_name',
	'grid' => true,
	'acl_grid' => 'admin_users',
	'acl_grid_multi' => 'admin_users_multi',
	'fields_create' => array(
		'Podstawowe dane' => array(
			'username' => array(
				'label' => 'Login',
				'description' => 'Używane wyłącznie do logowania',
				'type' => 'text',
				'validators' => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,username',
				'escape' => true,
			),
			'display_name' => array(
				'label' => 'Nazwa wyświetlana',
				'description' => 'Nazwa widoczna dla innych użytkowników',
				'type' => 'text',
				'validators' => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,display_name',
				'escape' => true,
			),
			'email' => array(
				'label' => 'E-mail',
				'description' => 'E-mail użytkownika',
				'type' => 'text',
				'validators' => 'required|max:70|email|unique:users,email',
				'escape' => true,
			),
			'password' => array(
				'label' => 'Hasło',
				'description' => 'Hasło potrzebne do logowania',
				'type' => 'password',
				'validators' => 'required',
			),
			'group_id' => array(
				'label' => 'Grupa',
				'description' => 'Grupa użytkowników',
				'type' => 'related',
				'validators' => 'required|exists:groups,id',
				'related_table' => 'groups',
				'related_key' => 'id',
				'related_value' => 'name',
			),
		),
	),
	'fields_edit' => array(
		'Podstawowe dane' => array(
			'username' => array(
				'label' => 'Login',
				'description' => 'Używane wyłącznie do logowania',
				'type' => 'text',
				'validators' => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,username,(:id)',
				'escape' => true,
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:30|alpha_dash|unique:users,slug,(:id)',
				'escape' => true,
			),
			'display_name' => array(
				'label' => 'Nazwa wyświetlana',
				'description' => 'Nazwa widoczna dla innych użytkowników',
				'type' => 'text',
				'validators' => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,display_name,(:id)',
				'escape' => true,
			),
			'email' => array(
				'label' => 'E-mail',
				'description' => 'E-mail użytkownika',
				'type' => 'text',
				'validators' => 'required|max:70|email|unique:users,email,(:id)',
				'escape' => true,
			),
			'password' => array(
				'label' => 'Hasło',
				'description' => 'Hasło potrzebne do logowania<br /><small>Zostaw puste jeśli bez zmian</small>',
				'type' => 'password',
				'validators' => '',
			),
			'group_id' => array(
				'label' => 'Grupa',
				'description' => 'Grupa użytkowników',
				'type' => 'related',
				'validators' => 'required|exists:groups,id',
				'related_table' => 'groups',
				'related_key' => 'id',
				'related_value' => 'name',
			),
		),
	),
);