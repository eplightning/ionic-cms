<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto wpis w blogu: %s',
	'delete_log_sub' => 'title',
	'edit_log' => 'Zmieniono wpis w blogu: %s',
	'edit_log_sub' => 'title',
	'table' => 'blogs',
	'edit' => true,
	'create' => false,
	'acl_delete' => 'admin_blogs_delete',
	'acl_edit' => 'admin_blogs_edit',
	'title' => 'Wpisy w blogach',
	'title_edit' => 'Edycja wpisu',
	'grid' => true,
	'acl_grid' => 'admin_blogs',
	'acl_grid_multi' => 'admin_blogs_multi',
	'fields_edit' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł wpisu',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:blogs,slug,(:id)',
				'escape' => true
			),
			'content_raw' => array(
				'label' => 'Treść',
				'description' => 'Treść wpisu',
				'type' => 'textarea',
				'validators' => 'required',
				'escape' => true
			),
		),
	),
);