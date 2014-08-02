<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto pole: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano pole: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono pole: %s',
	'edit_log_sub' => 'title',
	'table' => 'fields',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_fields_delete',
	'acl_edit' => 'admin_fields_edit',
	'acl_add' => 'admin_fields_add',
	'title' => 'Własne pola',
	'title_edit' => 'Edycja pola',
	'title_add' => 'Dodawanie pola',
	'grid' => true,
	'acl_grid' => 'admin_fields',
	'acl_grid_multi' => 'admin_fields_multi',
	'fields_create' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł pola',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Opis pola',
				'type' => 'textarea',
				'validators' => 'required|max:255',
				'escape' => true,
			),
			'type' => array(
				'label' => 'Typ',
				'description' => 'Typ pola',
				'type' => 'select',
				'validators' => 'required|in:text,number,textarea,select,checkbox',
				'options' => array('text' => 'Pole tekstowe', 'number' => 'Liczba', 'textarea' => 'Duże pole tekstowe', 'select' => 'Pole rozwijane', 'checkbox' => 'Pole wyboru')
			),
			'default' => array(
				'label' => 'Domyślna wartość',
				'description' => 'Domyślna wartość tego pola. W przypadku pola wyboru podaj wartość &quot;yes&quot; ,aby pole było domyślnie zaznaczone',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true,
			),
			'options' => array(
				'label' => 'Opcje',
				'description' => 'W przypadku pola rozwijanego podaj opcje w formacie: identyfikator=Wartość pola (jedno na linie)',
				'type' => 'textarea',
				'validators' => '',
				'escape' => true,
			),
		)
	),
	'fields_edit' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł pola',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Opis pola',
				'type' => 'textarea',
				'validators' => 'required|max:255',
				'escape' => true,
			),
			'type' => array(
				'label' => 'Typ',
				'description' => 'Typ pola',
				'type' => 'select',
				'validators' => 'required|in:text,number,textarea,select,checkbox',
				'options' => array('text' => 'Pole tekstowe', 'number' => 'Liczba', 'textarea' => 'Duże pole tekstowe', 'select' => 'Pole rozwijane', 'checkbox' => 'Pole wyboru')
			),
			'default' => array(
				'label' => 'Domyślna wartość',
				'description' => 'Domyślna wartość tego pola. W przypadku pola wyboru podaj wartość &quot;yes&quot; ,aby pole było domyślnie zaznaczone',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true,
			),
			'options' => array(
				'label' => 'Opcje',
				'description' => 'W przypadku pola rozwijanego podaj opcje w formacie: identyfikator=Wartość pola (jedno na linie)',
				'type' => 'textarea',
				'validators' => '',
				'escape' => true,
			),
		)
	),
);