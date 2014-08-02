<?php
return array(
	'delete' => false,
	'edit_log' => 'Zmieniono email: %s',
	'edit_log_sub' => 'title',
	'table' => 'emails',
	'edit' => true,
	'create' => false,
	'acl_edit' => 'admin_emails',
	'title' => 'E-maile systemowe',
	'title_edit' => 'Edycja emaila',
	'grid' => true,
	'acl_grid' => 'admin_emails',
	'acl_grid_multi' => 'admin_emails',
	'fields_edit' => array(
		'Dane' => array(
			'subject' => array(
				'label' => 'Temat e-maila',
				'description' => 'Temat wysyłanego e-maila',
				'type' => 'text',
				'validators' => 'required|max:255',
				'escape' => true
			),
			'message' => array(
				'label' => 'Treść',
				'description' => 'Treść wysyłanego e-maila',
				'type' => 'editor',
				'validators' => 'required',
			),
		),
	),
);