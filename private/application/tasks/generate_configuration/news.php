<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto news: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano news: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono news: %s',
	'edit_log_sub' => 'title',
	'table' => 'news',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_news_delete',
	'acl_edit' => 'admin_news_edit',
	'acl_add' => 'admin_news_add',
	'title' => 'Nowości',
	'title_edit' => 'Edycja newsa',
	'title_add' => 'Dodawanie newsa',
	'slug' => 'slug',
	'slug_from' => 'title',
	'grid' => true,
	'acl_grid' => 'admin_news',
	'acl_grid_multi' => 'admin_news_multi',
	'fields_create' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł newsa',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true
			),
			'source' => array(
				'label' => 'Źródło',
				'description' => 'Źródło newsa',
				'type' => 'text',
				'validators' => 'max:127',
				'escape' => true
			),
			'image_text' => array(
				'label' => 'Podpis do obrazka',
				'description' => 'Podpis do obrazka tego newsa',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true
			),
		),
		'Treść' => array(
			'news_content' => array(
				'label' => 'Pełna treść',
				'description' => 'Pełna treść newsa',
				'type' => 'editor',
				'validators' => 'required',
			),
			'news_short' => array(
				'label' => 'Skrócona treść',
				'description' => 'Skrócona treść newsa. Jeśli pusta zostanie automatycznie pobrany pierwszy akapit newsa.',
				'type' => 'textarea',
				'validators' => '',
			),
		),
		'Obrazki/tagi' => array(
			'big_image' => array(
				'label' => 'Duży obrazek',
				'type' => 'select',
				'options' => array(),
			),
			'small_image' => array(
				'label' => 'Mały obrazek',
				'type' => 'select',
				'options' => array(),
			),
			'tags' => array(
				'label' => 'Tagi',
				'type' => 'select',
				'options' => array(),
			),
		),
	),
	'fields_edit' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł newsa',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:news,slug,(:id)',
				'escape' => true
			),
			'source' => array(
				'label' => 'Źródło',
				'description' => 'Źródło newsa',
				'type' => 'text',
				'validators' => 'max:127',
				'escape' => true
			),
			'image_text' => array(
				'label' => 'Podpis do obrazka',
				'description' => 'Podpis do obrazka tego newsa',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true
			),
		),
		'Treść' => array(
			'news_content' => array(
				'label' => 'Pełna treść',
				'description' => 'Pełna treść newsa',
				'type' => 'editor',
				'validators' => 'required',
			),
			'news_short' => array(
				'label' => 'Skrócona treść',
				'description' => 'Skrócona treść newsa. Jeśli pusta zostanie automatycznie pobrany pierwszy akapit newsa.',
				'type' => 'textarea',
				'validators' => '',
			),
		),
		'Obrazki/tagi' => array(
			'big_image' => array(
				'label' => 'Duży obrazek',
				'type' => 'select',
				'options' => array(),
			),
			'small_image' => array(
				'label' => 'Mały obrazek',
				'type' => 'select',
				'options' => array(),
			),
			'tags' => array(
				'label' => 'Tagi',
				'type' => 'select',
				'options' => array(),
			),
		),
	),
);