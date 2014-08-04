<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto plik: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano plik: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono plik: %s',
	'edit_log_sub' => 'title',
	'table' => 'files',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_files_delete',
	'acl_edit' => 'admin_files_edit',
	'acl_add' => 'admin_files_add',
	'title' => 'Pliki',
	'title_edit' => 'Edycja pliku',
	'title_add' => 'Dodawanie pliku',
	'slug' => 'slug',
	'slug_from' => 'title',
	'grid' => true,
	'acl_grid' => 'admin_files',
	'acl_grid_multi' => 'admin_files',
	'fields_create' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł pliku',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'textarea' => array(
				'label' => 'Opis',
				'description' => 'Opis pliku',
				'type' => 'textarea',
				'validators' => '',
				'escape' => true,
			),
			'filename' => array(
				'label' => 'Nazwa pliku',
				'description' => 'Nazwa pliku z rozszerzeniem. Domyślnie zostanie użyta nazwa wrzuconego pliku.',
				'type' => 'text',
				'validators' => 'max:255|match:!^[\pL\pN\s\-\_\.]+\.[a-zA-Z]+$!u',
			),
		),
		'Upload' => array(
			'filelocation' => array(
				'label' => 'Plik',
				'description' => 'Właściwy plik. Maksymalny rozmiar zależy od ustawień serwera',
				'type' => 'upload',
				'validators' => 'required',
				'upload_dir' => 'files',
			),
			'image' => array(
				'label' => 'Screenshot',
				'description' => 'Obrazek do tego pliku',
				'type' => 'upload',
				'validators' => 'required|image',
				'upload_dir' => 'files',
			),
		),
	),
	'fields_edit' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł pliku',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:files,slug,(:id)',
				'escape' => true,
			),
			'textarea' => array(
				'label' => 'Opis',
				'description' => 'Opis pliku',
				'type' => 'textarea',
				'validators' => '',
				'escape' => true,
			),
			'filename' => array(
				'label' => 'Nazwa pliku',
				'description' => 'Nazwa pliku z rozszerzeniem. Domyślnie zostanie użyta nazwa wrzuconego pliku.',
				'type' => 'text',
				'validators' => 'max:255|match:!^[\pL\pN\s\-\_\.]+\.[a-zA-Z]+$!u',
			),
		),
		'Upload' => array(
			'filelocation' => array(
				'label' => 'Plik',
				'description' => 'Zostaw puste jeśli bez zmian',
				'type' => 'upload',
				'validators' => '',
				'upload_dir' => 'files',
			),
			'image' => array(
				'label' => 'Screenshot',
				'description' => 'Zostaw puste jeśli bez zmian',
				'type' => 'upload',
				'validators' => 'image',
				'upload_dir' => 'files',
			),
		),
	),
);