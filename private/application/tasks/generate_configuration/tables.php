<?php
return array(
	'delete' => true,
	'delete_log' => 'Usunięto tabelę: %s',
	'delete_log_sub' => 'title',
	'add_log' => 'Dodano tabelę: %s',
	'add_log_sub' => 'title',
	'edit_log' => 'Zmieniono tabelę: %s',
	'edit_log_sub' => 'title',
	'table' => 'tables',
	'edit' => true,
	'create' => true,
	'acl_delete' => 'admin_tables_delete',
	'acl_edit' => 'admin_tables_edit',
	'acl_add' => 'admin_tables_add',
	'title' => 'Tabele',
	'title_edit' => 'Edycja tabeli',
	'title_add' => 'Dodawanie tabeli',
	'grid' => true,
	'slug' => true,
	'slug_from' => 'title',
	'acl_grid' => 'admin_tables',
	'acl_grid_multi' => 'admin_tables_multi',
	'fields_create' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł tabeli',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'sorting_rules' => array(
				'label' => 'Sposób sortowania',
				'description' => 'Sposób sortowania używany przez generatora.',
				'type' => 'select',
				'validators' => 'required|in:laliga,standard',
				'options' => array('standard' => 'Standardowy', 'laliga' => 'La Liga')
			),
			'auto_generation' => array(
				'label' => 'Automatyczne generowanie',
				'description' => 'Przy edycji/dodawaniu meczów ta tabela ma być automatycznie generowana',
				'type' => 'checkbox'
			)
		),
		'Powiązane' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'Tabela dotyczy rozgrywek ...',
				'type' => 'related',
				'validators' => 'required|exists:competitions,id',
				'related_table' => 'competitions',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'season_id' => array(
				'label' => 'Sezon',
				'description' => 'W jakim sezonie',
				'type' => 'related',
				'validators' => 'required|exists:seasons,id',
				'related_table' => 'seasons',
				'related_key' => 'id',
				'related_value' => 'year',
			),
		)
	),
	'fields_edit' => array(
		'Dane' => array(
			'title' => array(
				'label' => 'Tytuł',
				'description' => 'Tytuł tabeli',
				'type' => 'text',
				'validators' => 'required|max:127',
				'escape' => true,
			),
			'slug' => array(
				'label' => 'Slug',
				'description' => 'Slug widoczny w linkach',
				'type' => 'text',
				'validators' => 'required|max:127|alpha_dash|unique:tables,slug,(:id)',
				'escape' => true
			),
			'sorting_rules' => array(
				'label' => 'Sposób sortowania',
				'description' => 'Sposób sortowania używany przez generatora.',
				'type' => 'select',
				'validators' => 'required|in:laliga,standard',
				'options' => array('standard' => 'Standardowy', 'laliga' => 'La Liga')
			),
			'auto_generation' => array(
				'label' => 'Automatyczne generowanie',
				'description' => 'Przy edycji/dodawaniu meczów ta tabela ma być automatycznie generowana',
				'type' => 'checkbox'
			)
		),
		'Powiązane' => array(
			'competition_id' => array(
				'label' => 'Rozgrywki',
				'description' => 'Tabela dotyczy rozgrywek ...',
				'type' => 'related',
				'validators' => 'required|exists:competitions,id',
				'related_table' => 'competitions',
				'related_key' => 'id',
				'related_value' => 'name',
			),
			'season_id' => array(
				'label' => 'Sezon',
				'description' => 'W jakim sezonie',
				'type' => 'related',
				'validators' => 'required|exists:seasons,id',
				'related_table' => 'seasons',
				'related_key' => 'id',
				'related_value' => 'year',
			),
		)
	),
);