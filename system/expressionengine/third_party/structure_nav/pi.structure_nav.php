<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
 * @since       Version 2.0
 * @filesource
 */

$plugin_info = array(
    'pi_name'   => 'Structure Nav',
    'pi_version'  => '1.0.7',
    'pi_author'   => 'rsanchez',
    'pi_author_url' => 'https://github.com/rsanchez',
    'pi_description'=> 'Tag pair for custom Structure navigation',
    'pi_usage'    => 'See https://github.com/rsanchez/structure_nav for usage info.',
);

/**
 * Structure Nav Plugin
 *
 * @package    ExpressionEngine
 * @subpackage Addons
 * @category   Plugin
 * @author     rsanchez
 * @link       https://github.com/rsanchez
 */
class Structure_nav
{
    public function __construct()
    {
        require_once PATH_THIRD.'structure_nav/libraries/Structure_nav_parser.php';
    }

    public function basic($add_entry_vars = FALSE)
    {
        $nav = new Structure_nav_parser();

        $variables = $nav->get_variables($add_entry_vars);

        unset($nav);

        if ( ! $variables)
        {
            return ee()->TMPL->no_results();
        }

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables);
    }

    public function advanced()
    {
        return $this->basic(TRUE);
    }
}

/* End of file pi.structure_nav.php */
/* Location: /system/expressionengine/third_party/structure_nav/pi.structure_nav.php */
