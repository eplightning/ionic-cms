<?php
namespace Ionic\Package;

/**
 * Package API
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class API {

    /**
     * Last menu ordering
     *
     * @var int
     */
    protected $last_ordering = null;

    /**
     * Add admin menu
     *
     * @param string $name
     * @param string $category
     * @param string $module
     * @param string $role
     * @param int    $ordering
     */
    public function add_admin_menu($name, $category, $module, $role = '', $ordering = null)
    {
        if ($ordering === null)
        {
            if ($this->last_ordering === null)
            {
                $this->last_ordering = (int) \DB::table('admin_menu')->orderby('sorting', 'desc')->only('sorting');
            }

            $this->last_ordering++;

            $ordering = $this->last_ordering;
        }
        else
        {
            if ($ordering > $this->last_ordering)
                $this->last_ordering = $ordering;
        }


        \DB::table('admin_menu')->insert(array(
            'title' => $name,
            'category' => $category,
            'module' => $module,
            'role' => $role,
            'sorting' => $ordering
        ));
    }

    /**
     * Add config entry
     *
     * @param int    $category_id
     * @param string $name
     * @param string $description
     * @param string $section
     * @param mixed  $value
     * @param string $type
     * @param string $options
     * @param string $php_type
     * @param string $php_key
     */
    public function add_config($category_id, $name, $description, $section, $value, $type, $options, $php_type, $php_key)
    {
        \DB::table('config')->insert(array(
            'category_id' => $category_id,
            'name'        => $name,
            'description' => $description,
            'section'     => $section,
            'value'       => ($php_type == 'bool' ? ($value ? '1' : '0') : $value),
            'type'        => $type,
            'options'     => $options,
            'php_type'    => $php_type,
            'php_key'     => $php_key
        ));
    }

    /**
     * Add config category
     *
     * @param  string $name
     * @param  string $description
     * @param  string $key
     * @return int
     */
    public function add_config_category($name, $description, $key)
    {
        return \DB::table('config_categories')->insert_get_id(array(
                    'name'        => $name,
                    'description' => $description,
                    'key'         => $key
                ));
    }

    /**
     * Add user modifiable e-mail
     *
     * @param string $title
     * @param string $default_subject
     * @param string $default_message
     * @param string $vars_info
     */
    public function add_email($title, $default_subject, $default_message, $vars_info = '')
    {
        \DB::table('emails')->insert(array(
            'title'   => $title,
            'subject' => $default_subject,
            'message' => $default_message,
            'vars'    => $vars_info
        ));
    }

    /**
     * Add ACL role
     *
     * @param string $name
     * @param string $title
     * @param string $section
     */
    public function add_role($name, $title, $section)
    {
        \DB::table('roles')->insert(array(
            'name'    => $name,
            'title'   => $title,
            'section' => $section
        ));
    }

    /**
     * Enable/disable admin menu
     *
     * @param string|array $menu
     * @param bool         $enable
     */
    public function disable_menu($menu, $enable = false)
    {
        \DB::table('admin_menu')->where_in('module', (array) $menu)->update(array(
            'is_hidden' => ($enable ? 0 : 1)
        ));
    }

    /**
     * Execute queries
     *
     * {dbp} gets replaced with current table prefix
     *
     * @param  array $queries
     * @param  bool  $transaction
     * @return bool
     */
    public function execute_queries(array $queries, $transaction = false)
    {
        if ($transaction)
            \DB::connection()->pdo->beginTransaction();

        foreach ($queries as $file)
        {
            try {
                \DB::connection()->query($file);
            } catch (Exception $e) {
                if ($transaction)
                    \DB::connection()->pdo->rollBack();

                return false;
            }
        }

        if ($transaction)
            \DB::connection()->pdo->commit();

        return true;
    }

    /**
     * Execute SQL file
     *
     * {dbp} gets replaced with current table prefix
     *
     * @param  string $file
     * @param  bool   $transaction
     * @return bool
     */
    public function execute_sql($file, $transaction = false)
    {
        if ($transaction)
            \DB::connection()->pdo->beginTransaction();

        if (!is_file($file))
            return false;

        $file = file_get_contents($file);
        $characters = strlen($file);
        $in_string = false;
        $string_chr = '';
        $query = '';
        $prefix = \DB::prefix();

        for ($i = 0; $i < $characters; $i++)
        {
            if ($file[$i] == '{' and $file[$i + 1] == 'd' and $file[$i + 2] == 'b' and $file[$i + 3] == 'p' and $file[$i + 4] == '}')
            {
                $query .= $prefix;

                $i += 4;
                continue;
            }

            if ($file[$i] == '"' or $file[$i] == '\'')
            {
                if ($in_string and $file[$i] == $string_chr and ($i > 0 and $file[$i - 1] != '\\'))
                {
                    $in_string = false;
                }
                elseif (!$in_string)
                {
                    $in_string = true;
                    $string_chr = $file[$i];
                }
            }

            if ($file[$i] == ';' and !$in_string and $query)
            {
                try {
                    \DB::connection()->query($query);
                    $query = '';
                } catch (Exception $e) {
                    if ($transaction)
                        \DB::connection()->pdo->rollBack();

                    return false;
                }
            }
            else
            {
                $query .= $file[$i];
            }
        }

        if (!empty($query))
        {
            try {
                \DB::connection()->query($query);
                $query = '';
            } catch (Exception $e) {
                if ($transaction)
                    \DB::connection()->pdo->rollBack();

                return false;
            }
        }

        if ($transaction)
            \DB::connection()->pdo->commit();

        return true;
    }

    /**
     * Remove admin menu by module
     *
     * @param string $module
     */
    public function remove_admin_menu($module)
    {
        \DB::table('admin_menu')->where('module', '=', $module)->delete();
    }

    /**
     * Remove config by category key and entry key
     *
     * @param string $category_key
     * @param string $entry_key
     */
    public function remove_config($category_key, $entry_key)
    {
        \DB::table('config')->join('config_categories', 'config.category_id', '=', 'config_categories.id')
                            ->where('config_categories.key', '=', $category_key)
                            ->where('config.php_key', '=', $entry_key)
                            ->delete();
    }

    /**
     * Remove config category by key
     *
     * @param string $key
     */
    public function remove_config_category($key)
    {
        \DB::table('config_categories')->where('key', '=', $key)->delete();
    }

    /**
     * Remove e-mail by title
     *
     * @param string $title
     */
    public function remove_email($title)
    {
        \DB::table('email')->where('title', '=', $title)->delete();
    }

    /**
     * Remove role by name
     *
     * @param string $name
     */
    public function remove_role($name)
    {
        \DB::table('roles')->where('name', '=', $name)->delete();
    }

    /**
     * Update package state in database
     *
     * Automatically creates new entry if needed
     *
     * @param string $id
     * @param string $version
     */
    public function update_package($id, $version)
    {
        $existing = \DB::table('packages')->where('id', '=', $id)->first('version');

        if ($existing)
        {
            \DB::table('packages')->where('id', '=', $id)->update(array('version' => $version));
        }
        else
        {
            \DB::table('packages')->insert(array(
                'id' => $id,
                'version' => $version,
                'is_disabled' => 0
            ));
        }
    }

}