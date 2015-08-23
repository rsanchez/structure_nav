<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Structure_nav_parser
{
    protected $original_TMPL;

    public $entry_ids = array();
    public $rows_by_entry = array();

    public function __construct()
    {
        $this->original_TMPL = ee()->TMPL;

        ee()->TMPL = clone $this->original_TMPL;
    }

    public function get_variables($add_entry_vars = false)
    {
        if ( ! class_exists('Structure'))
        {
            require_once PATH_THIRD.'structure/mod.structure.php';
        }

        $structure = new Structure();

        ee()->TMPL->tagparams['add_unique_ids'] = 'entry_id';

        unset(
            ee()->TMPL->tagparams['add_level_classes'],
            ee()->TMPL->tagparams['add_span'],
            ee()->TMPL->tagparams['css_class'],
            ee()->TMPL->tagparams['css_id'],
            ee()->TMPL->tagparams['current_class'],
            ee()->TMPL->tagparams['has_children_class'],
            ee()->TMPL->tagparams['include_ul']
        );

        $nav = $structure->nav();

        unset($structure);

        if ( ! $nav)
        {
            return array();
        }

        $charset = ee()->config->item('charset');

        $html = sprintf('<?xml version="1.0" encoding="%s"?><!doctype html>%s', $charset, $nav);

        $dom = new DOMDocument();

        $dom->loadHTML($html);

        $separator = ee()->config->item('word_separator') !== 'dash' ? '_' : '-';

        $ul = $dom->getElementById('nav'.$separator.'sub');

        $variables = $this->parse_ul($ul);

        if ($add_entry_vars)
        {
            // inject custom fields etc
            $query = ee()->db->select('channel_titles.*')
                ->select('channel_data.*')
                ->select('channels.field_group')
                ->select('channels.channel_title AS channel')
                ->select('channels.channel_name AS channel_short_name')
                ->where_in('channel_titles.entry_id', $this->entry_ids)
                ->join('channel_data', 'channel_data.entry_id = channel_titles.entry_id')
                ->join('channels', 'channels.channel_id = channel_titles.channel_id')
                ->get('channel_titles');

            foreach ($query->result_array() as $row)
            {
                if (isset($this->rows_by_entry[$row['entry_id']]))
                {
                    $this->add_entry_vars($this->rows_by_entry[$row['entry_id']], $row);
                }
            }
        }

        return $variables;
    }

    protected function get_vars_to_parse($prefix, $field_names)
    {
        $vars_to_parse = array();

        $prefix_length = strlen($prefix);

        foreach (ee()->TMPL->var_single as $tag)
        {
            if (strncmp($prefix, $tag, $prefix_length) !== 0)
            {
                continue;
            }

            $space = strpos($tag, ' ');

            if ($space === FALSE)
            {
                $name = $tag;
                $param_string = '';
            }
            else
            {
                $name = substr($tag, 0, $space);
                $param_string = substr($tag, $space + 1);
            }

            $field_name = substr($name, $prefix_length);

            $modifier = NULL;

            $has_modifier = strpos($field_name, ':');

            if ($has_modifier !== FALSE)
            {
                $modifier = substr($field_name, $has_modifier + 1);
                $field_name = substr($field_name, 0, $has_modifier);
            }

            if ( ! isset($field_names[$field_name]))
            {
                continue;
            }

            $params = ee()->functions->assign_parameters($param_string);

            $vars_to_parse[] = array(
                'modifier' => $modifier,
                'field_id' => $field_names[$field_name],
                'params' => $params,
                'tagdata' => FALSE,
                'replace' => $tag,
            );
        }

        foreach (ee()->TMPL->var_pair as $tag => $params)
        {
            if (strncmp($prefix, $tag, $prefix_length) !== 0)
            {
                continue;
            }

            $space = strpos(' ', $tag);

            if ($space === FALSE)
            {
                $name = $tag;
            }
            else
            {
                $name = substr($tag, 0, $space);
            }

            $field_name = substr($name, $prefix_length);

            if ( ! isset($field_names[$field_name]))
            {
                continue;
            }

            if (preg_match_all('#{'.preg_quote($tag).'}(.*?){/'.preg_quote($name).'}#s', ee()->TMPL->tagdata, $matches))
            {
                foreach ($matches[1] as $i => $tagdata)
                {
                    $replace = substr($matches[0][$i], 1, -1);

                    $vars_to_parse[] = array(
                        'modifier' => NULL,
                        'field_id' => $field_names[$field_name],
                        'params' => $params,
                        'tagdata' => $tagdata,
                        'replace' => $replace,
                    );
                }
            }
        }

        return $vars_to_parse;
    }

    protected function add_entry_vars(&$variable_row, $row)
    {
        ee()->load->library('api');

        ee()->api->instantiate('channel_fields');

        ee()->load->library('typography');

        $prefix = $variable_row['__prefix'];

        unset($variable_row['__prefix']);

        foreach ($row as $key => $value)
        {
            if (preg_match('/^field_(id|ft|dt)_/', $key))
            {
                continue;
            }

            $variable_row[$prefix.$key] = $value;
        }

        $field_names = $this->get_custom_fields_by_group($row['field_group']);

        $vars_to_parse = $this->get_vars_to_parse($prefix, $field_names);

        foreach ($vars_to_parse as $parse)
        {
            if ( ! isset($row['field_id_'.$parse['field_id']]))
            {
                continue;
            }

            $raw_value = $row['field_id_'.$parse['field_id']];

            if (ee()->api_channel_fields->setup_handler($parse['field_id']))
            {
                ee()->api_channel_fields->apply('_init', array(compact('row')));

                $data = ee()->api_channel_fields->apply('pre_process', array($raw_value));

                $args = array($data, $parse['params'], $parse['tagdata']);

                if ($parse['modifier'])
                {
                    $method = 'replace_'.$parse['modifier'];

                    if ( ! ee()->api_channel_fields->check_method_exists($method))
                    {
                        $args[] = $parse['modifier'];
                        $method = 'replace_tag_catchall';

                        if ( ! ee()->api_channel_fields->check_method_exists($method))
                        {
                            continue;
                        }
                    }
                }
                else
                {
                    $method = 'replace_tag';
                }

                $variable_row[$parse['replace']] = ee()->api_channel_fields->apply($method, $args);
            }
            else
            {
                $variable_row[$parse['replace']] = $raw_value;
            }
        }

        foreach ($field_names as $field_name => $field_id)
        {
            if ( ! isset($variable_row[$prefix.$field_name]))
            {
                $variable_row[$prefix.$field_name] = isset($row['field_id_'.$field_id]) ? $row['field_id_'.$field_id] : '';
            }
        }

        $variable_row['entry_id_path'] = array($row['entry_id'], array('path_variable' => TRUE));
        $variable_row['url_title_path'] = array($row['url_title'], array('path_variable' => TRUE));
        $variable_row['title_permalink'] = array($row['url_title'], array('path_variable' => TRUE));
    }

    protected function get_custom_fields_by_group($field_group)
    {
        if (empty($field_group))
        {
            return array();
        }

        $field_names = ee()->session->cache(__CLASS__, __FUNCTION__.':'.$field_group);

        // initialize the fields
        if ($field_names === FALSE)
        {
            $all_fields = $this->get_custom_fields();

            $fields = isset($all_fields[$field_group]) ? $all_fields[$field_group] : array();

            $field_names = array();

            foreach ($fields as $field)
            {
                $field_names[$field['field_name']] = $field['field_id'];

                if (isset(ee()->api_channel_fields->settings[$field['field_id']]))
                {
                    continue;
                }

                $field_settings = @unserialize(base64_decode($field['field_settings']));

                if (is_array($field_settings))
                {
                    $field_settings = array_merge($field_settings, $field);
                }
                else
                {
                    $field_settings = $field;
                }

                ee()->api_channel_fields->set_settings($field['field_id'], $field_settings);
            }

            ee()->session->cache(__CLASS__, __FUNCTION__.':'.$field_group, $field_names);
        }

        return $field_names;
    }

    protected function get_custom_fields()
    {
        if ( ! $fields = ee()->session->cache(__CLASS__, __FUNCTION__))
        {
            $query = ee()->db->get('channel_fields');

            $fields = array();

            foreach ($query->result_array() as $row)
            {
                if ( ! isset($fields[$row['group_id']]))
                {
                    $fields[$row['group_id']] = array();
                }

                $fields[$row['group_id']][$row['field_id']] = $row;
            }

            $query->free_result();

            ee()->session->set_cache(__CLASS__, __FUNCTION__, $fields);
        }

        return $fields;
    }

    protected function parse_ul(DOMElement $ul, $depth = 0)
    {
        if ($depth === 0)
        {
            $prefix = 'root:';
            $group = 'children';
        }
        else if ($depth === 1)
        {
            $prefix = 'child:';
            $group = 'grandchildren';
        }
        else
        {
            $prefix = str_repeat('great_', $depth - 2).'grandchild:';
            $group = str_repeat('great_', $depth - 1).'grandchildren';
        }

        $variables = array();

        $lis = array();

        foreach ($ul->childNodes as $li)
        {
            if (empty($li->firstChild))
            {
                continue;
            }

            $lis[] = $li;
        }

        $numLis = count($lis);

        $separator = ee()->config->item('word_separator') !== 'dash' ? '_' : '-';

        foreach ($lis as $i => $li)
        {
            $link = $li->firstChild;

            $class = $li->getAttribute('class');

            $entry_id = str_replace('nav'.$separator.'sub'.$separator, '', $li->getAttribute('id'));

            $variables[$i] = array(
                '__prefix' => $prefix,
                $prefix.'entry_id' => $entry_id,
                $prefix.'title' => $link->nodeValue,
                $prefix.'page_url' => $link->getAttribute('href'),
                $prefix.'page_uri' => str_replace(ee()->functions->create_url(''), '', $link->getAttribute('href')),
                $prefix.'first_child' => $i === 0,
                $prefix.'last_child' => ($i + 1) === $numLis,
                $prefix.'count' => $i + 1,
                $prefix.'total_results' => $numLis,
                $prefix.'active' => !! preg_match('/\bhere\b/', $class),
                $prefix.'has_active_child' => !! preg_match('/\bparent'.$separator.'here\b/', $class),
            );

            $childUl = $li->getElementsByTagName('ul')->item(0);

            $variables[$i][$prefix.'children'] = $childUl ? $this->parse_ul($childUl, $depth + 1) : array();
            $variables[$i][$prefix.'has_children'] = !! $variables[$i][$prefix.'children'];

            $this->entry_ids[] = $entry_id;

            $this->rows_by_entry[$entry_id] =& $variables[$i];
        }

        return $variables;
    }

    public function __destruct()
    {
        ee()->TMPL = $this->original_TMPL;
    }
}

/* End of file pi.structure_nav.php */
/* Location: /system/expressionengine/third_party/structure_nav/pi.structure_nav.php */