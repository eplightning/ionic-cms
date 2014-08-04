<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto newsletter: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano newsletter: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono newsletter: %s',
	'edit_log_sub' => 'title',
	'table' => 'newsletter',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_newsletter',
	'acl_edit' => 'admin_newsletter',
	'acl_add' => 'admin_newsletter',
	'title' => 'Newsletter',
	'title_edit' => 'Edycja newslettera',
	'title_add' => 'Dodawanie newslettera',
	'grid' => true,
	'acl_grid' => 'admin_newsletter',
	'acl_grid_multi' => 'admin_newsletter',
	'fields_create' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł wiadomości',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true,
			),
			'message' => array(
				'label' => 'Wiadomość',
				'description' => 'Treść wiadomości',
				'type' => 'editor',
				'validators' => 'required',
			),
		),
		'Typ wysyłki' => array(
			'type' => array(
				'label' => 'Rodzaj',
				'description' => 'Rodzaj wysyłki',
				'type' => 'select',
				'validators' => 'required|in:email,list,pm',
				'options' => array(
					'email' => 'E-maile użytkowników',
					'list' => 'Lista mailingowa',
					'pm' => 'Prywatne wiadomości'
				),
			),
			'ignore_settings' => array(
				'label' => 'Zignoruj ustawienia',
				'description' => 'Tylko w przypadku wybrania e-maili użytkowników. Wysyła poczte do wszystkich użytkowników, łącznie z tymi którzy na to się nie zgodzili.',
				'type' => 'checkbox'
			),
		)
	),
	'fields_edit' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł wiadomości',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true,
			),
			'message' => array(
				'label' => 'Wiadomość',
				'description' => 'Treść wiadomości',
				'type' => 'editor',
				'validators' => 'required',
			),
		),
	),
);