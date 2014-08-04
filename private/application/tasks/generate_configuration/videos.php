<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto video: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano video: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono video: %s',
	'edit_log_sub' => 'title',
	'table' => 'videos',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_videos_delete',
	'acl_edit' => 'admin_videos_edit',
	'acl_add' => 'admin_videos_add',
	'title' => 'Filmy',
	'title_edit' => 'Edycja video',
	'title_add' => 'Dodawanie video',
	'slug' => 'slug',
	'slug_from' => 'title',
	'grid' => true,
	'acl_grid' => 'admin_videos',
	'acl_grid_multi' => 'admin_videos_multi',
	'fields_create' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł video',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Opis filmu',
				'type' => 'textarea',
				'validators' => '',
				'escape' => true
			),
			'thumbnail' => array(
				'label' => 'Link do miniaturki',
				'description' => 'Jeśli zostawisz puste zostanie wygenerowana dla filmów z YouTube, Vimeo, Dailymotion oraz Flickr-a.',
				'type' => 'text',
				'validators' => 'url|max:127',
				'escape' => true,
			),
		),
		'Video' => array(
			'link' => array(
				'label' => 'Link do filmu',
				'description' => 'Poniższe pole zostanie wypełnione jeśli podasz tutaj link do video z YouTube, Vimeo, Dailymotion oraz Flickr-a.',
				'type' => 'text',
				'validators' => 'url|max:127',
				'escape' => true,
			),
			'embed' => array(
				'label' => 'HTML filmu',
				'description' => 'Kod HTML filmu. Nie wymagane jeśli podałeś link',
				'type' => 'textarea',
				'validators' => ''
			),
		)
	),
	'fields_edit' => array(
		'Podstawowe' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł video',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:videos,slug,(:id)',
				'escape' => true,
			),
			'description' => array(
				'label' => 'Opis',
				'description' => 'Opis filmu',
				'type' => 'textarea',
				'validators' => '',
				'escape' => true
			),
			'thumbnail' => array(
				'label' => 'Link do miniaturki',
				'description' => 'Jeśli zostawisz puste zostanie wygenerowana dla filmów z YouTube, Vimeo, Dailymotion oraz Flickr-a.',
				'type' => 'text',
				'validators' => 'url|max:127',
				'escape' => true,
			),
		),
		'Video' => array(
			'link' => array(
				'label' => 'Link do filmu',
				'description' => 'Poniższe pole zostanie wypełnione jeśli podasz tutaj link do video z YouTube, Vimeo, Dailymotion oraz Flickr-a.',
				'type' => 'text',
				'validators' => 'url|max:127',
				'escape' => true,
			),
			'embed' => array(
				'label' => 'HTML filmu',
				'description' => 'Kod HTML filmu. Nie wymagane jeśli podałeś link',
				'type' => 'textarea',
				'validators' => ''
			),
		)
	),
);