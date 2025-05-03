<?php

/**
 * Plugin Name: Ad Unblock
 * Description: Integrates your WordPress site with Ad Unblock service to recover ad revenue lost to ad blockers.
 * Version: 1.0.1
 * Author: Ad Unblock
 * Author URI: https://ad-unblock.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ad-unblock
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('AD_UNBLOCK_VERSION', '1.0.1');
define('AD_UNBLOCK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AD_UNBLOCK_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The core plugin class
 */
class Ad_Unblock
{
    /**
     * Cache duration in seconds
     */
    const CACHE_DURATION = 300; // 5 minutes

    /**
     * API endpoint for script sources
     */
    const API_ENDPOINT = 'https://config.adunblocker.com/valid_script_sources.json';

    /**
     * The instance of this class
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Initialize hooks
        add_action('plugins_loaded', array($this, 'initialize_plugin'));

        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));

        // Add plugin row meta
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
    }

    /**
     * Initialize the plugin
     */
    public function initialize_plugin()
    {
        // Admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        // Front-end functionality
        add_action('wp_head', array($this, 'insert_script'), 1);
    }

    /**
     * Add menu in admin panel
     */
    public function add_admin_menu()
    {
        add_options_page(
            esc_html__('Ad Unblock Settings', 'ad-unblock'),
            esc_html__('Ad Unblock', 'ad-unblock'),
            'manage_options',
            'ad-unblock',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings()
    {
        register_setting('ad_unblock_options', 'ad_unblock_verification_code', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));

        register_setting('ad_unblock_options', 'ad_unblock_page_rules', array(
            'sanitize_callback' => array($this, 'sanitize_page_rules'),
            'default' => array(
                'all_pages' => 'yes',
                'url_patterns' => '',
                'categories' => array(),
                'tags' => array(),
            ),
        ));

        add_settings_section(
            'ad_unblock_section_main',
            esc_html__('Main Settings', 'ad-unblock'),
            array($this, 'render_section_main'),
            'ad_unblock'
        );

        add_settings_field(
            'ad_unblock_verification_code',
            esc_html__('Verification Code', 'ad-unblock'),
            array($this, 'render_verification_code_field'),
            'ad_unblock',
            'ad_unblock_section_main'
        );

        add_settings_field(
            'ad_unblock_page_rules',
            esc_html__('Enable On Pages', 'ad-unblock'),
            array($this, 'render_page_rules_field'),
            'ad_unblock',
            'ad_unblock_section_main'
        );
    }

    /**
     * Sanitize page rules
     */
    public function sanitize_page_rules($input)
    {
        $sanitized = array(
            'all_pages' => isset($input['all_pages']) && $input['all_pages'] === 'yes' ? 'yes' : 'no',
            'url_patterns' => isset($input['url_patterns']) ? sanitize_textarea_field($input['url_patterns']) : '',
            'categories' => array(),
            'tags' => array(),
        );

        if (isset($input['categories']) && is_array($input['categories'])) {
            $sanitized['categories'] = array_map('intval', $input['categories']);
        }

        if (isset($input['tags']) && is_array($input['tags'])) {
            $sanitized['tags'] = array_map('intval', $input['tags']);
        }

        return $sanitized;
    }

    /**
     * Render the admin page
     */
    public function render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="ad-unblock-header">
                <div class="ad-unblock-header-content">
                    <div class="ad-unblock-logo-container">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'logo.png'); ?>" alt="Ad Unblock Logo" />
                        <span class="ad-unblock-logo-text">AdUnblock</span>
                    </div>

                    <h2><?php esc_html_e('Recover your ad revenue lost to ad blockers', 'ad-unblock'); ?></h2>
                    <p><?php esc_html_e('Ad Unblock helps you recover revenue lost to ad blockers with a simple integration.', 'ad-unblock'); ?></p>

                    <div class="ad-unblock-steps">
                        <strong><?php esc_html_e('How to get started:', 'ad-unblock'); ?></strong>
                        <ol>
                            <li><?php echo wp_kses(__('Register and create an account at <a href="https://ad-unblock.com" target="_blank">ad-unblock.com</a>', 'ad-unblock'), array('a' => array('href' => array(), 'target' => array()))); ?></li>
                            <li><?php esc_html_e('Add your website in the Ad Unblock dashboard', 'ad-unblock'); ?></li>
                            <li><?php esc_html_e('Go to Integration → Verification page to find your verification code', 'ad-unblock'); ?></li>
                            <li><?php esc_html_e('Enter the verification code below and configure on which pages the script should run', 'ad-unblock'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="ad-unblock-settings-form">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('ad_unblock_options');
                    do_settings_sections('ad_unblock');
                    submit_button();
                    ?>
                </form>
            </div>

            <div class="ad-unblock-footer">
                <p>
                    <?php
                    $support_link = sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url('https://ad-unblock.com/contact/'),
                        esc_html__('Get Support', 'ad-unblock')
                    );

                    printf(
                        /* translators: %1$s: plugin version, %2$s: support URL with HTML */
                        esc_html__('Ad Unblock Plugin v%1$s | %2$s', 'ad-unblock'),
                        esc_html(AD_UNBLOCK_VERSION),
                        wp_kses_post($support_link)
                    );
                    ?>
                </p>
            </div>
        </div>
    <?php
    }

    /**
     * Render main section description
     */
    public function render_section_main()
    {
        echo '<div class="ad-unblock-settings-section">';
        echo '<p>' . esc_html__('Configure your Ad Unblock integration settings below.', 'ad-unblock') . '</p>';
        echo '</div>';
    }

    /**
     * Render verification code field
     */
    public function render_verification_code_field()
    {
        $verification_code = get_option('ad_unblock_verification_code', '');
    ?>
        <div class="ad-unblock-field-wrapper">
            <input type="text" name="ad_unblock_verification_code" id="ad_unblock_verification_code"
                value="<?php echo esc_attr($verification_code); ?>" class="regular-text">
            <p class="description">
                <?php esc_html_e('Enter the verification code from your Ad Unblock dashboard (Integration → Verification page).', 'ad-unblock'); ?>
            </p>
        </div>
    <?php
    }

    /**
     * Render page rules field
     */
    public function render_page_rules_field()
    {
        $page_rules = get_option('ad_unblock_page_rules', array(
            'all_pages' => 'yes',
            'url_patterns' => '',
            'categories' => array(),
            'tags' => array(),
        ));
    ?>
        <fieldset>
            <legend class="screen-reader-text"><?php esc_html_e('Page Rules', 'ad-unblock'); ?></legend>

            <p>
                <label>
                    <input type="checkbox" name="ad_unblock_page_rules[all_pages]" value="yes"
                        <?php checked($page_rules['all_pages'], 'yes'); ?>>
                    <?php esc_html_e('Enable on all pages', 'ad-unblock'); ?>
                </label>
            </p>

            <p>
                <label for="ad_unblock_url_patterns"><?php esc_html_e('URL Patterns (one per line):', 'ad-unblock'); ?></label><br>
                <textarea name="ad_unblock_page_rules[url_patterns]" id="ad_unblock_url_patterns"
                    class="large-text code" rows="4"><?php echo esc_textarea($page_rules['url_patterns']); ?></textarea>
                <span class="description">
                    <?php esc_html_e('Enter URL patterns to include specific pages (e.g., /blog/, /product/*)', 'ad-unblock'); ?>
                </span>
            </p>

            <p>
                <label><?php esc_html_e('Categories:', 'ad-unblock'); ?></label><br>
                <?php
                $categories = get_categories(array('hide_empty' => false));
                foreach ($categories as $category) {
                ?>
                    <label>
                        <input type="checkbox" name="ad_unblock_page_rules[categories][]"
                            value="<?php echo esc_attr($category->term_id); ?>"
                            <?php checked(in_array($category->term_id, $page_rules['categories'] ?? array())); ?>>
                        <?php echo esc_html($category->name); ?>
                    </label><br>
                <?php
                }
                ?>
            </p>

            <p>
                <label><?php esc_html_e('Tags:', 'ad-unblock'); ?></label><br>
                <?php
                $tags = get_tags(array('hide_empty' => false));
                foreach ($tags as $tag) {
                ?>
                    <label>
                        <input type="checkbox" name="ad_unblock_page_rules[tags][]"
                            value="<?php echo esc_attr($tag->term_id); ?>"
                            <?php checked(in_array($tag->term_id, $page_rules['tags'] ?? array())); ?>>
                        <?php echo esc_html($tag->name); ?>
                    </label><br>
                <?php
                }
                ?>
            </p>
        </fieldset>
<?php
    }

    /**
     * Check if the current page should have the script
     */
    private function should_enable_on_current_page()
    {
        $page_rules = get_option('ad_unblock_page_rules', array(
            'all_pages' => 'yes',
            'url_patterns' => '',
            'categories' => array(),
            'tags' => array(),
        ));

        // All pages option is enabled
        if (isset($page_rules['all_pages']) && $page_rules['all_pages'] === 'yes') {
            return true;
        }

        // Check URL patterns
        if (!empty($page_rules['url_patterns'])) {
            $current_url = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            if (empty($current_url)) {
                return false;
            }

            $patterns = explode("\n", $page_rules['url_patterns']);

            foreach ($patterns as $pattern) {
                $pattern = trim($pattern);
                if (empty($pattern)) {
                    continue;
                }

                // Convert wildcard pattern to regex
                $regex = str_replace(
                    array('\*', '/'),
                    array('.*', '\/'),
                    preg_quote($pattern, '/')
                );

                if (preg_match('/^' . $regex . '/i', $current_url)) {
                    return true;
                }
            }
        }

        // Check categories and tags
        if (is_singular()) {
            global $post;

            // Check categories
            if (!empty($page_rules['categories'])) {
                $post_categories = wp_get_post_categories($post->ID);
                foreach ($post_categories as $category) {
                    if (in_array($category, $page_rules['categories'])) {
                        return true;
                    }
                }
            }

            // Check tags
            if (!empty($page_rules['tags'])) {
                $post_tags = wp_get_post_tags($post->ID, array('fields' => 'ids'));
                foreach ($post_tags as $tag) {
                    if (in_array($tag, $page_rules['tags'])) {
                        return true;
                    }
                }
            }
        }

        // Check category archive pages
        if (is_category() && !empty($page_rules['categories'])) {
            $category = get_queried_object_id();
            if (in_array($category, $page_rules['categories'])) {
                return true;
            }
        }

        // Check tag archive pages
        if (is_tag() && !empty($page_rules['tags'])) {
            $tag = get_queried_object_id();
            if (in_array($tag, $page_rules['tags'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the script sources from API with caching
     */
    private function get_script_sources()
    {
        $cache_key = 'ad_unblock_script_sources';
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $response = wp_remote_get(self::API_ENDPOINT);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            // Cache failed responses for 1 minute to prevent hammering
            set_transient($cache_key, array(), 60);
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $script_sources = json_decode($body, true);

        if (!is_array($script_sources)) {
            set_transient($cache_key, array(), 60);
            return array();
        }

        // Cache for 5 minutes
        set_transient($cache_key, $script_sources, self::CACHE_DURATION);

        return $script_sources;
    }

    /**
     * Insert the script into the page head
     */
    public function insert_script()
    {
        $verification_code = get_option('ad_unblock_verification_code', '');

        if (empty($verification_code) || !$this->should_enable_on_current_page()) {
            return;
        }

        $script_sources = $this->get_script_sources();

        if (empty($script_sources) || !is_array($script_sources)) {
            return;
        }

        // Get the first script source only
        $script_url = reset($script_sources);

        // Add verification code as a meta tag in DOM
        echo '<meta name="ad-unblock-verification" content="' . esc_attr($verification_code) . '" />';

        // Enqueue the single script
        wp_enqueue_script(
            'ad-unblock-script',
            esc_url($script_url),
            array(),
            null,
            array('strategy' => 'async')
        );
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook)
    {
        if ('settings_page_ad-unblock' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'ad-unblock-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array(),
            AD_UNBLOCK_VERSION
        );
    }

    /**
     * Add settings link to plugin actions
     */
    public function add_plugin_action_links($links)
    {
        $settings_link = '<a href="' . admin_url('options-general.php?page=ad-unblock') . '">' . esc_html__('Settings', 'ad-unblock') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add additional links to plugin row meta
     */
    public function add_plugin_row_meta($links, $file)
    {
        if (plugin_basename(__FILE__) === $file) {
            $row_meta = array(
                'docs' => '<a href="' . esc_url('https://ad-unblock.com/documentation/') . '" target="_blank">' . esc_html__('Documentation', 'ad-unblock') . '</a>',
                'support' => '<a href="' . esc_url('https://ad-unblock.com/support/') . '" target="_blank">' . esc_html__('Support', 'ad-unblock') . '</a>',
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }
}

// Initialize the plugin
Ad_Unblock::get_instance();
