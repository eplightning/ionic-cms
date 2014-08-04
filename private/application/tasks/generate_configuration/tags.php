<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto tag: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano tag: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono tag: %s',
	'edit_log_sub' => 'title',
	'table' => 'tags',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_tags_delete',
	'acl_edit' => 'admin_tags_edit',
	'acl_add' => 'admin_tags_add',
	'title' => 'Tagi',
	'title_edit' => 'Edycja tagu',
	'title_add' => 'Dodawanie tagu',
	'slug' => 'slug',
	'slug_from' => 'title',
	'grid' => true,
	'acl_grid' => 'admin_tags',
	'acl_grid_multi' => 'admin_tags_multi',
	'fields_create' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł tagu',
				'type' => 'text',
				'validators' => 'required|max:127|unique:tags,title',
				'escape' => true,
			),
		),
	),
	'fields_edit' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł tagu',
				'type' => 'text',
				'validators' => 'required|max:127|unique:tags,title,(:id)',
				'escape' => true,
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:tags,slug,(:id)',
				'escape' => true,
			),
		),
	),
);