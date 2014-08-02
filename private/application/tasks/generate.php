<?php
class Generate_Task {

	public function add_template($config, $directory)
	{
		$file = '<h2>'.$config['title_add'].'</h2>'."\n";

		$has_files = false;

		foreach ($config['fields_create'] as $section)
		{
			foreach ($section as $k => $v)
			{
				if ($v['type'] == 'upload')
				{
					$has_files = true;
					break;
				}
			}

			if ($has_files) break;
		}

		if (!$has_files)
		{
			$file .= "<form action=\"admin/".$config['table']."/add\" method=\"post\">\n";
		}
		else
		{
			$file .= "<form action=\"admin/".$config['table']."/add\" method=\"post\" enctype=\"multipart/form-data\">\n";
		}

		foreach ($config['fields_create'] as $k => $section)
		{
			$file .= "	<div class=\"section\">
		<div class=\"theader\">
			<div class=\"theader2\">".$k."</div>
		</div>
		<div class=\"section-content\">\n";

			foreach ($section as $name => $v)
			{
				$file .= "			<div class=\"elem\">\n";

				if (isset($v['label']))
				{
					$file .= "				<label for=\"".$name."\">".$v['label']."".(isset($v['description']) ? ('<br /><small>'.$v['description'].'</small>') : '')."</label>\n";
					$file .= "				<div class=\"right\">\n";
				}

				$file .= "				{% if errors.has('".$name."') %}
					<div class=\"error\">{{ errors.first('".$name."') }}</div>
				{% endif %}\n";

				if ($v['type'] == 'text')
				{
					$file .= "				<input type=\"text\" id=\"".$name."\" name=\"".$name."\" value=\"{{ old_data.".$name."|e }}\" />\n";
				}
				elseif ($v['type'] == 'textarea')
				{
					$file .= "				".Form::textarea($name, "{{ old_data.".$name."|e }}", array('id' => $name))."\n";
				}
				elseif ($v['type'] == 'editor')
				{
					$file .= "				".\Ionic\Editor::create($name, "{{ old_data.".$name."|e }}")."\n";
				}
				elseif ($v['type'] == 'password')
				{
					$file .= "				<input type=\"password\" id=\"".$name."\" name=\"".$name."\" value=\"{{ old_data.".$name."|e }}\" />\n";
				}
				elseif ($v['type'] == 'checkbox')
				{
					$file .= "				<input class=\"checkbox\" type=\"checkbox\" id=\"".$name."\" name=\"".$name."\" value=\"1\"{% if old_data.".$name." == '1' %} checked=\"checked\"{% endif %} />\n";
				}
				elseif ($v['type'] == 'upload')
				{
					$file .= "				<input type=\"file\" id=\"".$name."\" name=\"".$name."\" />\n";
				}
				elseif ($v['type'] == 'related')
				{
					$file .= "				<select id=\"".$name."\" name=\"".$name."\">\n";

					$file .= "				{% for k, v in related_".$name." %}\n";

					$file .= "					<option value=\"{{ k }}\"{% if old_data.".$name." == k %} selected=\"selected\"{% endif %}>{{ v|e }}</option>\n";

					$file .= "				{% endfor %}\n";

					$file .= "				</select>\n";
				}
				elseif ($v['type'] == 'select')
				{
					$file .= "				<select id=\"".$name."\" name=\"".$name."\">\n";

					foreach ($v['options'] as $k2 => $v2)
					{
						$file .= "					<option value=\"".$k2."\"{% if old_data.".$name." == '".$k2."' %} selected=\"selected\"{% endif %}>".HTML::specialchars($v2)."</option>\n";
					}

					$file .= "				</select>\n";
				}

				if (isset($v['label']))
				{
					$file .= "				</div>";
				}

				$file .= "\n			</div>\n";
			}


			$file .= "		</div>
	</div>\n";
		}

		$file .= "	<div class=\"toolbar ui-widget-header ui-corner-all\">
		{{ form_token() }}
		<input type=\"submit\" name=\"submit\" style=\"width: auto\" value=\"Zapisz\" />
	</div>
</form>
<script type=\"text/javascript\">
$(function(){
	$('input[type=\"submit\"]').button();
});
</script>";

		file_put_contents($directory.DS.'add.twig', $file);

		echo '[+] Widok add.twig wygenerowany'."\n";
	}

	public function edit_template($config, $directory)
	{
		$file = '<h2>'.$config['title_edit'].'</h2>'."\n";

		$has_files = false;

		foreach ($config['fields_edit'] as $section)
		{
			foreach ($section as $k => $v)
			{
				if ($v['type'] == 'upload')
				{
					$has_files = true;
					break;
				}
			}

			if ($has_files) break;
		}

		if (!$has_files)
		{
			$file .= "<form action=\"admin/".$config['table']."/edit/{{ object.id }}\" method=\"post\">\n";
		}
		else
		{
			$file .= "<form action=\"admin/".$config['table']."/edit/{{ object.id }}\" method=\"post\" enctype=\"multipart/form-data\">\n";
		}

		foreach ($config['fields_edit'] as $k => $section)
		{
			$file .= "	<div class=\"section\">
		<div class=\"theader\">
			<div class=\"theader2\">".$k."</div>
		</div>
		<div class=\"section-content\">\n";

			foreach ($section as $name => $v)
			{
				$file .= "			<div class=\"elem\">\n";

				if (isset($v['label']))
				{
					$file .= "				<label for=\"".$name."\">".$v['label']."".(isset($v['description']) ? ('<br /><small>'.$v['description'].'</small>') : '')."</label>\n";
					$file .= "				<div class=\"right\">\n";
				}

				$file .= "				{% if errors.has('".$name."') %}
					<div class=\"error\">{{ errors.first('".$name."') }}</div>
				{% endif %}\n";

				$col = isset($v['column']) ? $v['column'] : $name;
				$cole = $col;

				if (!isset($v['escape']) or !$v['escape'])
				{
					$cole = $cole.'|e';
				}

				if ($v['type'] == 'text')
				{
					$file .= "				<input type=\"text\" id=\"".$name."\" name=\"".$name."\" value=\"{% if old_data.".$name." != '' %}{{ old_data.".$name."|e }}{% else %}{{ object.".$cole." }}{% endif %}\" />\n";
				}
				elseif ($v['type'] == 'textarea')
				{
					$file .= "				"."<textarea id=\"".$name."\" name=\"".$name."\" rows=\"10\" cols=\"50\">{% if old_data.".$name." != '' %}{{ old_data.".$name."|e }}{% else %}{{ object.".$cole." }}{% endif %}</textarea>\n";
				}
				elseif ($v['type'] == 'editor')
				{
					$file .= "				".\Ionic\Editor::create($name, "{% if old_data.".$name." != '' %}{{ old_data.".$name."|e }}{% else %}{{ object.".$cole." }}{% endif %}")."\n";
				}
				elseif ($v['type'] == 'password')
				{
					$file .= "				<input type=\"password\" id=\"".$name."\" name=\"".$name."\" value=\"{{ old_data.".$name."|e }}\" />\n";
				}
				elseif ($v['type'] == 'checkbox')
				{
					$file .= "				<input class=\"checkbox\" type=\"checkbox\" id=\"".$name."\" name=\"".$name."\" value=\"1\"{% if object.".$col." == '".(isset($v['checkbox_yes']) ? $v['checkbox_yes'] : '1')."' %} checked=\"checked\"{% endif %} />\n";
				}
				elseif ($v['type'] == 'upload')
				{
					$file .= "				<input type=\"file\" id=\"".$name."\" name=\"".$name."\" />\n";
				}
				elseif ($v['type'] == 'related')
				{
					$file .= "				<select id=\"".$name."\" name=\"".$name."\">\n";

					$file .= "				{% for k, v in related_".$name." %}\n";

					$file .= "					<option value=\"{{ k }}\"{% if (old_data.".$name." == k) or (object.".$col." == k and old_data.".$name." == '') %} selected=\"selected\"{% endif %}>{{ v|e }}</option>\n";

					$file .= "				{% endfor %}\n";

					$file .= "				</select>\n";
				}
				elseif ($v['type'] == 'select')
				{
					$file .= "				<select id=\"".$name."\" name=\"".$name."\">\n";

					foreach ($v['options'] as $k2 => $v2)
					{
						$file .= "					<option value=\"".$k2."\"{% if (old_data.".$name." == '".$k2."') or (object.".$col." == '".$k2."' and old_data.".$name." == '') %} selected=\"selected\"{% endif %}>".HTML::specialchars($v2)."</option>\n";
					}

					$file .= "				</select>\n";
				}

				if (isset($v['label']))
				{
					$file .= "				</div>";
				}

				$file .= "\n			</div>\n";
			}


			$file .= "		</div>
	</div>\n";
		}

		$file .= "	<div class=\"toolbar ui-widget-header ui-corner-all\">
		{{ form_token() }}
		<input type=\"submit\" name=\"submit\" style=\"width: auto\" value=\"Zapisz\" />
	</div>
</form>
<script type=\"text/javascript\">
$(function(){
	$('input[type=\"submit\"]').button();
});
</script>";

		file_put_contents($directory.DS.'edit.twig', $file);

		echo '[+] Widok edit.twig wygenerowany'."\n";
	}

	public function add($config, &$file)
	{
		if (isset($config['acl_add']))
		{
			$file .= "\t\tif (!Auth::can('".$config['acl_add']."')) return Response::error(403);\n";
		}

		$file .= "\n\t\tif (!Request::forged() and Request::method() == 'POST')\n\t\t{\n";

		$names = array();
		$mapping = array();
		$related = array();
		$files = array();
		$editor = false;

		foreach ($config['fields_create'] as $section)
		{
			foreach ($section as $k => $v)
			{
				if ($v['type'] == 'editor') $editor = true;

				if ($v['type'] == 'upload')
				{
					$files[$k] = array('key' => $k, 'dir' => $v['upload_dir'], 'column' => isset($v['column']) ? $v['column'] : $k);
					continue;
				}

				$names[] = $k;

				$mapping[$k] = array(
					'column' => isset($v['column']) ? $v['column'] : $k,
					'escape' => isset($v['escape']) ? (bool) $v['escape'] : false,
					'int'    => isset($v['int']) ? (bool) $v['int'] : false,
					'float'    => isset($v['float']) ? (bool) $v['float'] : false,
					'checkbox' => ($v['type'] == 'checkbox'),
					'checkbox_yes' => isset($v['checkbox_yes']) ? $v['checkbox_yes'] : '1',
					'checkbox_no' => isset($v['checkbox_no']) ? $v['checkbox_no'] : '0',
					'editor' => ($v['type'] == 'editor')
				);

				if ($v['type'] == 'related')
				{
					$related[$k] = array('table' => $v['related_table'], 'key' => $v['related_key'], 'value' => $v['related_value']);
				}
			}
		}

		echo ' - Mapowanie kolumn wygenerowane'."\n";

		$file .= "\t\t\t\$raw_data = array(";

		$t = array();

		foreach ($names as $v)
		{
			$t[] = "'".$v."' => ''";
		}

		$file .= implode(', ', $t).");\n";

		$file .= "\t\t\t\$raw_data = array_merge(\$raw_data, Input::only(array('".implode("', '", $names)."')));\n";

		if (!empty($files))
		{
			foreach ($files as $v)
			{
				$file .= "\t\t\t\$raw_data['".$v['key']."'] = Input::file('".$v['key']."');\n";
			}
		}

		$file .= "\n\t\t\t\$rules = array(\n";

		$t = array();

		foreach ($config['fields_create'] as $section)
		{
			foreach ($section as $k => $v)
			{
				$t[] = "'".$k."' => '".(isset($v['validators']) ? $v['validators'] : '')."'";
			}
		}

		$file .= "\t\t\t\t".implode(",\n\t\t\t\t", $t);

		$file .= "\n\t\t\t);\n\n";

		echo ' - Reguly walidacji wygenerowane'."\n";

		$file .= "\t\t\t\$validator = Validator::make(\$raw_data, \$rules);\n\n\t\t\tif (\$validator->fails())\n\t\t\t{";

		$file .= "\n\t\t\t\treturn Redirect::to('admin/".$config['table']."/add')->with_errors(\$validator)\n\t\t\t\t               ->with_input('only', array('".implode("', '", $names)."'));";

		$file .= "\n\t\t\t}\n\t\t\telse\n\t\t\t{\n";

		$file .= "\t\t\t\t\$prepared_data = array(\n";

		$t = array();

		foreach ($mapping as $k => $v)
		{
			if ($v['checkbox'])
			{
				$t[] = "'".$v['column']."' => (\$raw_data['".$k."'] == '1' ? '".$v['checkbox_yes']."' : '".$v['checkbox_no']."')";
			}
			elseif ($v['escape'])
			{
				$t[] = "'".$v['column']."' => HTML::specialchars(\$raw_data['".$k."'])";
			}
			elseif ($v['int'])
			{
				$t[] = "'".$v['column']."' => (int) \$raw_data['".$k."']";
			}
			elseif ($v['float'])
			{
				$t[] = "'".$v['column']."' => (float) \$raw_data['".$k."']";
			}
			else
			{
				$t[] = "'".$v['column']."' => \$raw_data['".$k."']";
			}
		}

		if (isset($config['slug']) and $config['slug'])
		{
			$t[] = "'".$config['slug']."' => ionic_tmp_slug('".$config['table']."')";
		}

		$file .= "\t\t\t\t\t".implode(",\n\t\t\t\t\t", $t);

		$file .= "\n\t\t\t\t);";

		if ($editor)
		{
			$file .= "\n\n\t\t\t\tif (!Auth::can('admin_root'))\n\t\t\t\t{\n\t\t\t\t\trequire_once path('app').'vendor'.DS.'htmLawed.php';\n";

			foreach ($mapping as $k => $v)
			{
				if ($v['editor'])
				{
					$file .= "\n\t\t\t\t\t\$prepared_data['".$v['column']."'] = htmLawed(\$prepared_data['".$v['column']."'], array('safe' => 1));";
				}
			}

			$file .= "\n\t\t\t\t}";
		}

		foreach ($files as $v)
		{
			$file .= "\n\n\t\t\t\tif (is_array(\$raw_data['".$v['key']."']) and \$raw_data['".$v['key']."']['error'] == UPLOAD_ERR_OK and !empty(\$raw_data['".$v['key']."']['name']) and !empty(\$raw_data['".$v['key']."']['tmp_name']))
				{
					\$filename = Str::ascii(\$raw_data['".$v['key']."']['name']);
					\$filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', \$filename);
					\$filename = preg_replace('/\s+/', '_', \$filename);
					\$extension = strtolower(substr(strrchr(\$filename, '.'), 1));

					while (file_exists(path('public').'upload'.DS.'".$v['dir']."'.DS.\$filename))
					{
						\$filename = Str::random(10).'.'.\$extension;
					}

					move_uploaded_file(\$raw_data['".$v['key']."']['tmp_name'], path('public').'upload'.DS.'".$v['dir']."'.DS.\$filename);

					\$prepared_data['".$v['column']."'] = \$filename;
				}";
		}

		$file .= "\n\n\t\t\t\t\$obj_id = DB::table('".$config['table']."')->insert_get_id(\$prepared_data);\n\n";

		if (isset($config['slug']) and $config['slug'])
		{
			$file .= "\t\t\t\tDB::table('".$config['table']."')->where('id', '=', \$obj_id)->update(array('".$config['slug']."' => ionic_find_slug(\$prepared_data['".$config['slug_from']."'], \$obj_id, '".$config['table']."')));\n\n";
		}

		$file .= "\t\t\t\t\$this->notice('Obiekt dodany pomyślnie');\n";

		if (isset($config['add_log']))
		{
			if (isset($config['add_log_sub']))
			{
				$file .= "\t\t\t\t\$this->log(sprintf('".$config['add_log']."', \$prepared_data['".$config['add_log_sub']."']));\n";
			}
			else
			{
				$file .= "\t\t\t\t\$this->log('".$config['add_log']."');\n";
			}
		}

		$file .= "\t\t\t\treturn Redirect::to('admin/".$config['table']."/index');";

		$file .= "\n\t\t\t}";

		$file .= "\n\t\t}\n";

		echo ' - Dodawanie do bazy danych wygenerowane'."\n";

		$file .= "\n\t\t\$this->page->set_title('".$config['title_add']."');\n\n";
		$file .= "\t\t\$this->page->breadcrumb_append('".$config['title']."', 'admin/".$config['table']."/index');\n";
		$file .= "\t\t\$this->page->breadcrumb_append('".$config['title_add']."', 'admin/".$config['table']."/add');\n\n";

		$file .= "\t\t\$this->view = View::make('admin.".$config['table'].".add');\n\n";

		$file .= "\t\t\$old_data = array(";

		$t = array();

		foreach ($names as $v)
		{
			$t[] = "'".$v."' => ''";
		}

		$file .= implode(', ', $t).");\n";

		$file .= "\t\t\$old_data = array_merge(\$old_data, Input::old());\n";

		$file .= "\t\t\$this->view->with('old_data', \$old_data);\n\n";

		if ($editor) $file .= "\t\tIonic\\Editor::init();\n\n";

		foreach ($related as $k => $v)
		{
			$file .= "\t\t\$related = array();\n\n";

			$file .= "\t\tforeach (DB::table('".$v['table']."')->get(array('".$v['key']."', '".$v['value']."')) as \$v)\n\t\t{";

			$file .= "\n\t\t\t\$related[\$v->".$v['key']."] = \$v->".$v['value'].";\n";

			$file .= "\t\t}\n\n\t\t\$this->view->with('related_".$k."', \$related);";
		}

		echo '[+] Akcja dodawania wygenerowana pomyslnie'."\n";
	}

	public function edit($config, &$file)
	{
		// ACL
		if (isset($config['acl_edit']))
		{
			$file .= "\t\tif (!Auth::can('".$config['acl_edit']."') or !ctype_digit(\$id)) return Response::error(403);\n";
		}
		else
		{
			$file .= "\t\tif (!ctype_digit(\$id)) return Response::error(500);\n";
		}

		$file .= "\n\t\t\$id = DB::table('".$config['table']."')->where('id', '=', (int) \$id)->first('*');\n";
		$file .= "\t\tif (!\$id) return Response::error(500);\n\n";

		$file .= "\t\tif (!Request::forged() and Request::method() == 'POST')\n\t\t{\n";

		$names = array();
		$mapping = array();
		$related = array();
		$files = array();
		$editor = false;

		foreach ($config['fields_edit'] as $section)
		{
			foreach ($section as $k => $v)
			{
				if ($v['type'] == 'editor') $editor = true;

				if ($v['type'] == 'upload')
				{
					$files[$k] = array('key' => $k, 'dir' => $v['upload_dir'], 'column' => isset($v['column']) ? $v['column'] : $k, 'remove' => ((isset($v['upload_remove'])) ? (bool) $v['upload_remove'] : false));
					continue;
				}

				$names[] = $k;

				$mapping[$k] = array(
					'column' => isset($v['column']) ? $v['column'] : $k,
					'escape' => isset($v['escape']) ? (bool) $v['escape'] : false,
					'int'    => isset($v['int']) ? (bool) $v['int'] : false,
					'float'    => isset($v['float']) ? (bool) $v['float'] : false,
					'checkbox' => ($v['type'] == 'checkbox'),
					'checkbox_yes' => isset($v['checkbox_yes']) ? $v['checkbox_yes'] : '1',
					'checkbox_no' => isset($v['checkbox_no']) ? $v['checkbox_no'] : '0',
					'editor' => ($v['type'] == 'editor')
				);

				if ($v['type'] == 'related')
				{
					$related[$k] = array('table' => $v['related_table'], 'key' => $v['related_key'], 'value' => $v['related_value']);
				}
			}
		}

		echo ' - Mapowanie kolumn wygenerowane'."\n";

		$file .= "\t\t\t\$raw_data = array(";

		$t = array();

		foreach ($names as $v)
		{
			$t[] = "'".$v."' => ''";
		}

		$file .= implode(', ', $t).");\n";

		$file .= "\t\t\t\$raw_data = array_merge(\$raw_data, Input::only(array('".implode("', '", $names)."')));\n";

		if (!empty($files))
		{
			foreach ($files as $v)
			{
				$file .= "\t\t\t\$raw_data['".$v['key']."'] = Input::file('".$v['key']."');\n";
			}
		}

		$file .= "\n\t\t\t\$rules = array(\n";

		$t = array();

		foreach ($config['fields_edit'] as $section)
		{
			foreach ($section as $k => $v)
			{
				$t[] = "'".$k."' => '".(isset($v['validators']) ? str_replace('(:id)', "'.\$id->id.'", $v['validators']) : '')."'";
			}
		}

		$file .= "\t\t\t\t".implode(",\n\t\t\t\t", $t);

		$file .= "\n\t\t\t);\n\n";

		echo ' - Reguly walidacji wygenerowane'."\n";

		$file .= "\t\t\t\$validator = Validator::make(\$raw_data, \$rules);\n\n\t\t\tif (\$validator->fails())\n\t\t\t{";

		$file .= "\n\t\t\t\treturn Redirect::to('admin/".$config['table']."/edit/'.\$id->id)->with_errors(\$validator)\n\t\t\t\t               ->with_input('only', array('".implode("', '", $names)."'));";

		$file .= "\n\t\t\t}\n\t\t\telse\n\t\t\t{\n";

		$file .= "\t\t\t\t\$prepared_data = array(\n";

		$t = array();

		foreach ($mapping as $k => $v)
		{
			if ($v['checkbox'])
			{
				$t[] = "'".$v['column']."' => (\$raw_data['".$k."'] == '1' ? '".$v['checkbox_yes']."' : '".$v['checkbox_no']."')";
			}
			elseif ($v['escape'])
			{
				$t[] = "'".$v['column']."' => HTML::specialchars(\$raw_data['".$k."'])";
			}
			elseif ($v['int'])
			{
				$t[] = "'".$v['column']."' => (int) \$raw_data['".$k."']";
			}
			elseif ($v['float'])
			{
				$t[] = "'".$v['column']."' => (float) \$raw_data['".$k."']";
			}
			else
			{
				$t[] = "'".$v['column']."' => \$raw_data['".$k."']";
			}
		}

		$file .= "\t\t\t\t\t".implode(",\n\t\t\t\t\t", $t);

		$file .= "\n\t\t\t\t);";

		if ($editor)
		{
			$file .= "\n\n\t\t\t\tif (!Auth::can('admin_root'))\n\t\t\t\t{\n\t\t\t\t\trequire_once path('app').'vendor'.DS.'htmLawed.php';\n";

			foreach ($mapping as $k => $v)
			{
				if ($v['editor'])
				{
					$file .= "\n\t\t\t\t\t\$prepared_data['".$v['column']."'] = htmLawed(\$prepared_data['".$v['column']."'], array('safe' => 1));";
				}
			}

			$file .= "\n\t\t\t\t}";
		}

		foreach ($files as $v)
		{
			$file .= "\n\n\t\t\t\tif (is_array(\$raw_data['".$v['key']."']) and \$raw_data['".$v['key']."']['error'] == UPLOAD_ERR_OK and !empty(\$raw_data['".$v['key']."']['name']) and !empty(\$raw_data['".$v['key']."']['tmp_name']))
				{\n";

			if ($v['remove'])
			{
				$file .= "					if (\$id->".$v['key'].")
					{
						@unlink(path('public').'upload'.DS.'".$v['dir']."'.DS.\$id->".$v['key'].");
					}\n\n";
			}

			$file .="					\$filename = Str::ascii(\$raw_data['".$v['key']."']['name']);
					\$filename = preg_replace('![^\.\_\pL\pN\s]+!', '', \$filename);
					\$filename = preg_replace('/\s+/', '_', \$filename);
					\$extension = strtolower(substr(strrchr(\$filename, '.'), 1));

					while (file_exists(path('public').'upload'.DS.'".$v['dir']."'.DS.\$filename))
					{
						\$filename = Str::random(10).'.'.\$extension;
					}

					move_uploaded_file(\$raw_data['".$v['key']."']['tmp_name'], path('public').'upload'.DS.'".$v['dir']."'.DS.\$filename);

					\$prepared_data['".$v['column']."'] = \$filename;
				}";
		}

		$file .= "\n\n\t\t\t\t\DB::table('".$config['table']."')->where('id', '=', \$id->id)->update(\$prepared_data);\n\n";

		$file .= "\t\t\t\t\$this->notice('Obiekt zaaktualizowany pomyślnie');\n";

		if (isset($config['edit_log']))
		{
			if (isset($config['edit_log_sub']))
			{
				$file .= "\t\t\t\t\$this->log(sprintf('".$config['edit_log']."', \$prepared_data['".$config['edit_log_sub']."']));\n";
			}
			else
			{
				$file .= "\t\t\t\t\$this->log('".$config['edit_log']."');\n";
			}
		}

		$file .= "\t\t\t\treturn Redirect::to('admin/".$config['table']."/index');";

		$file .= "\n\t\t\t}";

		$file .= "\n\t\t}\n";

		echo ' - Edytowanie bazy danych wygenerowane'."\n";

		$file .= "\n\t\t\$this->page->set_title('".$config['title_edit']."');\n\n";
		$file .= "\t\t\$this->page->breadcrumb_append('".$config['title']."', 'admin/".$config['table']."/index');\n";
		$file .= "\t\t\$this->page->breadcrumb_append('".$config['title_edit']."', 'admin/".$config['table']."/edit/'.\$id->id);\n\n";

		$file .= "\t\t\$this->view = View::make('admin.".$config['table'].".edit');\n\n";

		$file .= "\t\t\$old_data = array(";

		$t = array();

		foreach ($names as $v)
		{
			$t[] = "'".$v."' => ''";
		}

		$file .= implode(', ', $t).");\n";

		$file .= "\t\t\$old_data = array_merge(\$old_data, Input::old());\n";

		$file .= "\t\t\$this->view->with('old_data', \$old_data);\n\n";

		$file .= "\t\t\$this->view->with('object', \$id);\n\n";

		if ($editor) $file .= "\t\tIonic\\Editor::init();\n\n";

		foreach ($related as $k => $v)
		{
			$file .= "\t\t\$related = array();\n\n";

			$file .= "\t\tforeach (DB::table('".$v['table']."')->get(array('".$v['key']."', '".$v['value']."')) as \$v)\n\t\t{";

			$file .= "\n\t\t\t\$related[\$v->".$v['key']."] = \$v->".$v['value'].";\n";

			$file .= "\t\t}\n\n\t\t\$this->view->with('related_".$k."', \$related);";
		}

		echo '[+] Akcja edycji wygenerowana pomyslnie'."\n";
	}

	public function delete($config, &$file)
	{
		// ACL
		if (isset($config['acl_delete']))
		{
			$file .= "\t\tif (!Auth::can('".$config['acl_delete']."') or !ctype_digit(\$id)) return Response::error(403);\n";
		}
		else
		{
			$file .= "\t\tif (!ctype_digit(\$id)) return Response::error(500);\n";
		}

		$file .= "\n\t\t\$id = DB::table('".$config['table']."')->where('id', '=', (int) \$id)->first('*');\n";
		$file .= "\t\tif (!\$id) return Response::error(500);\n\n";

		$file .= "		if (!(\$status = \$this->confirm()))
		{
			return;
		}
		elseif (\$status == 2)
		{
			return Redirect::to('admin/".$config['table']."/index');
		}

		DB::table('".$config['table']."')->where('id', '=', \$id->id)->delete();

		\$this->notice('Obiekt usunięty pomyślnie');\n";

		if (isset($config['delete_log']))
		{
			if (isset($config['delete_log_sub']))
			{
				$file .= "\t\t\$this->log(sprintf('".$config['delete_log']."', \$id->".$config['delete_log_sub']."));\n";
			}
			else
			{
				$file .= "\t\t\$this->log('".$config['delete_log']."');\n";
			}
		}

		$file .= "\t\treturn Redirect::to('admin/".$config['table']."/index');";

		echo '[+] Akcja usuwania wygenerowana pomyslnie'."\n";
	}

	public function run($file)
	{
		echo '|||| Ionic admin generator task: '."\n";

		$config = require_once dirname(__FILE__).'/generate_configuration/'.$file[0].'.php';

		echo 'Plik konfiguracyjny zaladowany'."\n";

		$results_directory = dirname(__FILE__).DS.'generate_results'.DS.$file[0];

		if (!file_exists($results_directory)) mkdir($results_directory);

		echo 'Katalog utworzony'."\n";

		$controller_file = "<?php
class Admin_".ucfirst($file[0])."_Controller extends Admin_Controller {

";

		/**
		 * ADD ACTION
		 */
		if (isset($config['create']) and $config['create'])
		{
			echo 'Generowanie akcji dodawania:'."\n";

			$controller_file .= "\tpublic function action_add()\n\t{\n";

			$this->add($config, $controller_file);
			$this->add_template($config, $results_directory);

			$controller_file .= "\n\t}\n\n";
		}

		/**
		 * GRID #1
		 */
		if (isset($config['grid']) and $config['grid'])
		{
			echo 'Generowanie akcji siatki (autocomplete):'."\n";

			$controller_file .= "\tpublic function action_autocomplete(\$id)\n\t{\n";

			if (isset($config['acl_grid']))
			{
				$controller_file .= "\t\tif (!Auth::can('".$config['acl_grid']."')) return Response::error(403);\n\n";
			}

			$controller_file .= "		\$grid = \$this->make_grid();

		return \$grid->handle_autocomplete(\$id);";

			$controller_file .= "\n\t}\n\n";
		}

		/**
		 * DELETE ACTION
		 */
		if (isset($config['delete']) and $config['delete'])
		{
			echo 'Generowanie akcji usuwania:'."\n";

			$controller_file .= "\tpublic function action_delete(\$id)\n\t{\n";

			$this->delete($config, $controller_file);

			$controller_file .= "\n\t}\n\n";
		}

		/**
		 * EDIT ACTION
		 */
		if (isset($config['edit']) and $config['edit'])
		{
			echo 'Generowanie akcji edycji:'."\n";

			$controller_file .= "\tpublic function action_edit(\$id)\n\t{\n";

			$this->edit($config, $controller_file);
			$this->edit_template($config, $results_directory);

			$controller_file .= "\n\t}\n\n";
		}

		/**
		 * GRID #2
		 */
		if (isset($config['grid']) and $config['grid'])
		{
			echo 'Generowanie akcji siatki (filter, index, multiaction, sort, make_grid):'."\n";

			// Filter
			$controller_file .= "\tpublic function action_filter(\$id, \$value = null)\n\t{\n";

			if (isset($config['acl_grid']))
			{
				$controller_file .= "\t\tif (!Auth::can('".$config['acl_grid']."')) return Response::error(403);\n\n";
			}

			$controller_file .= "		\$grid = \$this->make_grid();

		return \$grid->handle_filter(\$id, \$value);";

			$controller_file .= "\n\t}\n\n";

			// Index
			$controller_file .= "\tpublic function action_index(\$id = null)\n\t{\n";

			if (isset($config['acl_grid']))
			{
				$controller_file .= "\t\tif (!Auth::can('".$config['acl_grid']."')) return Response::error(403);\n\n";
			}

			$controller_file .= "\t\t\$this->page->set_title('".$config['title']."');\n";
			$controller_file .= "\t\t\$this->page->breadcrumb_append('".$config['title']."', 'admin/".$config['table']."/index');\n\n";

			$controller_file .= "		\$grid = \$this->make_grid();

		\$result = \$grid->handle_index(\$id);

		if (\$result instanceof View)
		{
			\$this->view = \$result;
		}
		elseif (\$result instanceof Response)
		{
			return \$result;
		}";

			$controller_file .= "\n\t}\n\n";

			// Multiaction
			$controller_file .= "\tpublic function action_multiaction(\$name)\n\t{\n";

			if (isset($config['acl_grid_multi']))
			{
				$controller_file .= "\t\tif (!Auth::can('".$config['acl_grid_multi']."')) return Response::error(403);\n\n";
			}

			$controller_file .= "		\$grid = \$this->make_grid();

		return \$grid->handle_multiaction(\$name);";

			$controller_file .= "\n\t}\n\n";

			// Sort
			$controller_file .= "\tpublic function action_sort(\$item)\n\t{\n";

			if (isset($config['acl_grid']))
			{
				$controller_file .= "\t\tif (!Auth::can('".$config['acl_grid']."')) return Response::error(403);\n\n";
			}

			$controller_file .= "		\$grid = \$this->make_grid();

		return \$grid->handle_sort(\$item);";

			$controller_file .= "\n\t}\n\n";

			// Make grid
			$controller_file .= "\tprotected function make_grid()\n\t{\n";

			$controller_file .= "		\$grid = new Ionic\Grid('".$config['table']."', '".$config['title']."', 'admin/".$config['table']."');



		return \$grid;";

			$controller_file .= "\n\t}\n\n";
		}

		$controller_file .= "}";

		file_put_contents($results_directory.DS.$file[0].'.php', $controller_file);
	}
}