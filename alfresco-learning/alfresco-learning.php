<?php
/*
 * Plugin Name: Alfresco Learning
 * Description: Custom functionality for Alfresco Learning. 
 */

defined('ABSPATH') or die('Nobody screws with Boris Grishenko!');

class Alfresco {
    /*
     * Setup core functionality
     */
    function __construct() {        
        require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');

        $this->configureWordpress();
        $this->registerCustomPostTypes();
        $this->registerAJAXFunctions();
        $this->setupOutseta();
        $this->registerScripts();
    }

    /*
     * Setup Wordpress config
     */
    private function configureWordpress() {
        // remove noise from the header
        remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'wp_shortlink_wp_head');
		remove_action('wp_head', 'rest_output_link_wp_head');
    }

    /*
     * Register any required custom post types
     */
    private function registerCustomPostTypes() {
        add_action('init', function() {
            register_taxonomy('year-group', [], [
                'label' => 'Year Group',
                'public' => true,
                'hierarchical' => false,
                'show_in_rest' => true
            ]);
        });

        add_action('init', function() {
            register_taxonomy('subject', [], [
                'label' => 'Subject',
                'public' => true,
                'hierarchical' => false,
                'show_in_rest' => true
            ]);
        });

        add_action('init', function() {
            register_post_type('al_planning_unit',[
                'labels' => [
                    'name' => 'Planning Units',
                    'singular_name' => 'Planning Unit'
                ],
                'public' => true,
                'has_archive' => false,
                'rewrite' => ['slug' => 'hub-unit'],
                'menu_icon' => 'dashicons-welcome-learn-more',
                'show_in_rest' => true,
                'supports' => ['editor', 'title', 'revisions', 'thumbnail']
            ]);
        });

        add_action('init', function() {
            register_taxonomy_for_object_type('year-group', 'al_planning_unit');
            register_taxonomy_for_object_type('subject', 'al_planning_unit');
        });
    }

    /*
     * Register any required ajax endpoints
     */
    private function registerAJAXFunctions() {
        $alfrescoAJAX = new AlfrescoAJAX();
        $alfrescoAJAX->register();
    }

    /*
     * Add the Outseta scripts to site head
     */
    private function setupOutseta() {
        add_action('wp_head', function() {
            ?>
            <script>
                var o_options = {
                    domain: 'alfresco-learning.outseta.com',
                    load: 'auth,customForm,emailList,leadCapture,nocode,profile,support'
                };
            </script>
            <script src="https://cdn.outseta.com/outseta.min.js"
                data-options="o_options">
            </script>
            <script>
                Outseta.on('redirect',  (redirectUrl) => {
                    const redirectURL = new URL(redirectUrl);
                    const accessToken = redirectURL.searchParams.get('access_token');

                    if (accessToken) {
                        const newRedirectURL = new URL("/hub-unit/test-unit", window.origin);
                        newRedirectURL.searchParams.set('access_token', accessToken);
                        window.location.href = newRedirectURL.href;
                        return false;
                    }
                });
            </script>
            <?php
        });
    }

    /*
     * Register any additional js scripts that are required
     */
    private function registerScripts() {
        add_action('wp_enqueue_scripts', function() {
            if (is_singular('al_planning_unit')) {
                wp_enqueue_script('al-ph-unit', plugin_dir_url(__FILE__) . '/js/phUnit.js');
            }
        });
    }

}

$alfresco = new Alfresco();