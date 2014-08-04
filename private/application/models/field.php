<?php
namespace Model;

use \DB;
use \HTML;
use \Str;

class Field {

    public static function get_fields($user_id = 0)
    {
        $values = array();

        if ($user_id)
        {
            foreach (DB::table('field_values')->where('user_id', '=', $user_id)->get(array('field_id', 'value')) as $fv)
            {
                $values[$fv->field_id] = $fv->value;
            }
        }

        $fields = array();

        foreach (DB::table('fields')->get('*') as $f)
        {
            $fields[$f->id] = array('title'       => $f->title, 'description' => $f->description, 'type'        => $f->type, 'value'       => isset($values[$f->id]) ? $values[$f->id] : $f->default, 'options'     => $f->options, 'html'        => '');

            $html = '';

            if ($f->type == 'text' or $f->type == 'number')
            {
                $html = '<input type="text" name="field-'.$f->id.'" id="field-'.$f->id.'" maxlength="255" value="'.$fields[$f->id]['value'].'" />';
            }
            elseif ($f->type == 'textarea')
            {
                $html = '<textarea name="field-'.$f->id.'" id="field-'.$f->id.'" cols="50" rows="10">'.$fields[$f->id]['value'].'</textarea>';
            }
            elseif ($f->type == 'date')
            {
                $html = '<input type="text" class="datepicker" name="field-'.$f->id.'" id="field-'.$f->id.'" maxlength="10" value="'.$fields[$f->id]['value'].'" />';
            }
            elseif ($f->type == 'checkbox')
            {
                $html = '<input type="checkbox" class="checkbox" style="width: auto" name="field-'.$f->id.'" id="field-'.$f->id.'" value="1"'.(($fields[$f->id]['value'] == '1' or $fields[$f->id]['value'] == 'yes') ? ' checked="checked"' : '').' />';
            }
            elseif ($f->type == 'select')
            {
                $options = unserialize($f->options);

                if (is_array($options))
                {
                    $html = \Form::select('field-'.$f->id, $options, $fields[$f->id]['value'], array('id' => 'field-'.$f->id));
                }
            }

            $fields[$f->id]['html'] = $html;
        }

        return $fields;
    }

    public static function get_field_values($user_id = 0)
    {
        $values = array();

        if ($user_id)
        {
            foreach (DB::table('field_values')->where('user_id', '=', $user_id)->get(array('field_id', 'value')) as $fv)
            {
                $values[$fv->field_id] = $fv->value;
            }
        }

        $fields = array();

        foreach (DB::table('fields')->get('*') as $f)
        {
            $value = isset($values[$f->id]) ? $values[$f->id] : $f->default;

            if (empty($value) and $f->type != 'checkbox')
                continue;

            $fields[$f->id] = array('title'       => $f->title, 'description' => $f->description, 'type'        => $f->type, 'value'       => $value, 'options'     => $f->options);

            if ($f->type == 'select')
            {
                $options = unserialize($f->options);

                if (is_array($options) and isset($options[$fields[$f->id]['value']]))
                {
                    $fields[$f->id]['value'] = $options[$fields[$f->id]['value']];
                }
            }
            elseif ($f->type == 'checkbox')
            {
                $fields[$f->id]['value'] = ($value == 'yes' or $value == '1') ? 'Tak' : 'Nie';
            }
            elseif ($f->type == 'date')
            {
                $fields[$f->id]['value'] = ionic_date($value, 'short');
            }
        }

        return $fields;
    }

    public static function update_fields($user_id)
    {
        $values = array();

        if ($user_id)
        {
            foreach (DB::table('field_values')->where('user_id', '=', $user_id)->get(array('field_id', 'value')) as $fv)
            {
                $values[$fv->field_id] = $fv->value;
            }
        }

        $fields = array();

        foreach (DB::table('fields')->get('*') as $f)
        {
            if ($f->type == 'text')
            {
                if (\Input::has('field-'.$f->id))
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => HTML::specialchars(Str::limit(\Input::get('field-'.$f->id), 255, ''))
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => HTML::specialchars(Str::limit(\Input::get('field-'.$f->id), 255, ''))
                        ));
                    }
                }
                else
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => ''
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => ''
                        ));
                    }
                }
            }
            elseif ($f->type == 'number')
            {
                if (\Input::has('field-'.$f->id) and ctype_digit(\Input::get('field-'.$f->id)))
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => \Input::get('field-'.$f->id)
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => \Input::get('field-'.$f->id)
                        ));
                    }
                }
                else
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => ''
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => ''
                        ));
                    }
                }
            }
            elseif ($f->type == 'textarea')
            {
                if (\Input::has('field-'.$f->id))
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => HTML::specialchars(Str::limit(\Input::get('field-'.$f->id), 1024, ''))
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => HTML::specialchars(Str::limit(\Input::get('field-'.$f->id), 1024, ''))
                        ));
                    }
                }
                else
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => ''
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => ''
                        ));
                    }
                }
            }
            elseif ($f->type == 'date')
            {
                if (\Input::has('field-'.$f->id) and strtotime(\Input::get('field-'.$f->id)))
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => date('Y-m-d', strtotime(\Input::get('field-'.$f->id)))
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => date('Y-m-d', strtotime(\Input::get('field-'.$f->id)))
                        ));
                    }
                }
                else
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => ''
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => ''
                        ));
                    }
                }
            }
            elseif ($f->type == 'checkbox')
            {
                if (\Input::has('field-'.$f->id))
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => (\Input::get('field-'.$f->id) == '1' ? '1' : '0'),
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => (\Input::get('field-'.$f->id) == '1' ? '1' : '0')
                        ));
                    }
                }
                else
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => '0'
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => '0'
                        ));
                    }
                }
            }
            elseif ($f->type == 'select')
            {
                $options = unserialize($f->options);

                if (!is_array($options))
                {
                    continue;
                }

                if (\Input::has('field-'.$f->id) and isset($options[\Input::get('field-'.$f->id)]))
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => \Input::get('field-'.$f->id)
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => \Input::get('field-'.$f->id)
                        ));
                    }
                }
                else
                {
                    if (isset($values[$f->id]))
                    {
                        DB::table('field_values')->where('user_id', '=', (int) $user_id)->where('field_id', '=', $f->id)->update(array(
                            'value' => $f->default
                        ));
                    }
                    else
                    {
                        DB::table('field_values')->insert(array(
                            'user_id'  => $user_id,
                            'field_id' => $f->id,
                            'value'    => $f->default
                        ));
                    }
                }
            }
        }
    }

}