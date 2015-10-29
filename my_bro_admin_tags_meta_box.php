<?php
/*
Plugin Name: My bro admin meta box
Plugin URI: http://alkoweb.ru/
Author: Petrozavodsky
Author URI: http://alkoweb.ru/
Version: 1.0.0
Text Domain: my_bro_related_tags
*/

if (!defined('ABSPATH')) exit;

class My_Bro_Admin_Meta_Box
{
    protected static $version;
    protected static $text_domain;
    protected static $post_type;
    protected static $meta_box_id;
    protected static $base_path_url;
    protected static $post_prefix;
    protected static $limit_tags;
    protected static $set_exclude_terms;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function run()
    {
        self::$text_domain = 'my_bro_related_tags';
        self::$limit_tags = 0;
        self::$base_path_url = plugin_dir_url(__FILE__);
        self::$version = '1.0.0';
        self::$post_type = 'post';
        self::$meta_box_id = 'related_tags';
        self::$post_prefix = 'related_tags';
        self::set_exclude_terms();
        add_action('plugins_loaded', array(__CLASS__, 'text_domain'));
        add_action('admin_menu', array(__CLASS__, 'remove_meta_box_tags'));
        add_action('add_meta_boxes', array(__CLASS__, 'field'), 1);
        add_action('save_post', array(__CLASS__, 'update_meta_box'), 0);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'add_admin_scripts'), 10);
        add_action('wp_ajax_my_bro_admin_meta_box', array(__CLASS__, 'ajax_add_terms'));


    }

    public static function text_domain()
    {
        load_textdomain(self::$text_domain, plugin_dir_path(__FILE__) . 'lang/' . self::$text_domain . '-' . get_locale() . '.mo');
    }


    public static function remove_meta_box_tags()
    {
        remove_meta_box('tagsdiv-post_tag', 'post', 'advanced');
    }

    public static function add_admin_scripts()
    {
        wp_register_script('meta-box-tags-js', self::$base_path_url . 'admin/js/meta-box-tags.js', array('jquery'), self::$version, true);
        wp_enqueue_script('meta-box-tags-js');

        wp_register_style('meta-box-tags-css', self::$base_path_url . 'admin/css/meta-box-tags.css', array(), self::$version);
        wp_enqueue_style('meta-box-tags-css');
    }

    public static function add_tags($id, $terms)
    {
        wp_set_object_terms($id, $terms, 'post_tag', false);
    }

    public static function field()
    {
        add_meta_box(self::$meta_box_id, __('Popular tags', self::$text_domain), array(__CLASS__, 'get_meta_box'), self::$post_type, 'side', 'high');
    }


    public static function ajax_add_terms()
    {

        $res = '';
        $m_id = self::$meta_box_id;
        $current_val = array();
        $param = 'count';

        if (isset($_REQUEST['param'])) {
            $param = trim($_POST['param']);
        }

        $args = array(
            'orderby' => $param,
            'order' => 'DESC',
            'fields' => 'all'
        );

        $args['exclude'] = self::$set_exclude_terms;

        $terms = get_terms('post_tag', $args);
        ob_start();
        foreach ($terms as $val): ?>
            <div class="block__<?php echo $m_id; ?>-item">
                <label for="<?php echo self::$post_prefix . '[' . $val->term_id . ']'; ?>">
                    <input type="checkbox" name="<?php echo self::$post_prefix . '[' . $val->term_id . ']'; ?>"
                           value="<?php echo $val->term_id; ?>" <?php self::checked_helper($val, $current_val); ?> >
                    <?php
                    if($param =='count'){
                        echo $val->name . ' ('.$val->count.')';
                    }else{
                        echo $val->name;
                    }
                    ?>
                </label>
            </div>
        <?php endforeach;
        $res .= ob_get_contents();
        ob_end_clean();
        wp_send_json(array('html' => $res));
    }

    public static function get_meta_box()
    {
        global $post;
        $m_id = self::$meta_box_id;
        $current_val = self::get_all_post_terms();

        $args = array(
            'number' => self::$limit_tags,
            'orderby' => 'count',
            'order' => 'DESC',
            'fields' => 'all'
        );
        $args['exclude'] = self::$set_exclude_terms;
        $terms = get_terms('post_tag', $args);
        ?>
        <div class="block__<?php echo $m_id; ?>">
            <div class="block__<?php echo $m_id; ?>-overlay hide">
                <span class="spinner is-active"></span>
            </div>
            <div class="block__<?php echo $m_id; ?>-control">
                <label>
                    <?php _e('Sort by:', self::$text_domain); ?>
                    <select
                        data-meta-url="<?php echo site_url('/wp-admin/admin-ajax.php?action=my_bro_admin_meta_box&post_id=' . $post->ID); ?>">
                        <option value="name"><?php _e('by title', self::$text_domain); ?></option>
                        <option value="count"><?php _e('the number of entries', self::$text_domain); ?></option>
                    </select>

                </label>
            </div>
            <div class="block__<?php echo $m_id; ?>-items">
                <?php foreach ($terms as $val): ?>
                    <div class="block__<?php echo $m_id; ?>-item">
                        <label for="<?php echo self::$post_prefix . '[' . $val->term_id . ']'; ?>">
                            <input type="checkbox" name="<?php echo self::$post_prefix . '[' . $val->term_id . ']'; ?>"
                                   value="<?php echo $val->term_id; ?>" <?php self::checked_helper($val->term_id, $current_val); ?> >
                            <?php echo $val->name . ' ('.$val->count.')';  ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
    }

    public static function get_all_post_terms()
    {
        global $post;
        $tags = get_the_terms($post->ID, 'post_tag');
        $arr = array();
        foreach ($tags as $val) {
            array_push($arr, strval($val->term_id));
        }
        return $arr;
    }

    public static function checked_helper($var, $arr, $echo = true)
    {
        $res = '';
        if (in_array($var, $arr)) {
            $res .= ' checked="checked" ';
        }

        if ($echo) {
            echo $res;
        } else {
            return $res;
        }
    }

    public static function update_meta_box($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }
        if (!isset($_POST[self::$post_prefix])) {
            return false;
        };

        $fields = array();
        foreach ($_POST[self::$post_prefix] as $key => $value) {
            array_push($fields, $key);
        }

        if (count($fields) > 0) {
            self::add_tags($post_id, $fields);
        }

        return $post_id;
    }


    public static function  set_exclude_terms()
    {
        $arr = array();
        self::$set_exclude_terms = apply_filters( 'my_bro_related_tags_exclude', $arr);
    }

}

function my_bro_admin_meta_box_init()
{
    My_Bro_Admin_Meta_Box::run();
}

add_action('plugins_loaded', 'my_bro_admin_meta_box_init', 0);


