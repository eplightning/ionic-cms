<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto komentarz: %d',
	'delete_log_sub' => 'id',
	'edit_log' => 'Zmieniono komentarz: %d',
	'edit_log_sub' => 'id',
	'table' => 'comments',
	'edit' => true,
	'acl_delete' => 'admin_comments_delete',
	'acl_edit' => 'admin_comments_edit',
	'title' => 'Komentarze',
	'title_edit' => 'Edycja komentarza',
	'slug' => 'slug',
	'slug_from' => 'title',
	'grid' => true,
	'acl_grid' => 'admin_comments',
	'acl_grid_multi' => 'admin_comments_multi',
	'fields_edit' => array(
		'Treśc' => array(
			'raw_comment' => array(
				'label' => 'Treść',
				'description' => 'Treść komentarza',
				'type' => 'textarea',
				'validators' => 'required',
				'escape' => true
			),
		),
		'Inne' => array(
			'karma' => array(
				'label' => 'Karma',
				'description' => 'Ilość punktów',
				'type' => 'text',
				'validators' => 'required|numeric',
			),
			'guest_name' => array(
				'label' => 'Nazwa gościa',
				'description' => 'Dotyczy tylko komentarzy gości',
				'type' => 'text',
				'validators' => 'match:!^[\pL\pN\s]+$!u',
			),
		),
	),
);