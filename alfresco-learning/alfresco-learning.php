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
        $this->setupMailerlite();
        $this->setupGoogleAnalytics();
        $this->registerScripts();
        $this->onboardingLoginCheck();
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
            register_taxonomy('planning-category', [], [
                'label' => 'Planning Category',
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
            register_taxonomy_for_object_type('planning-category', 'al_planning_unit');
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
                        const newRedirectURL = new URL("/planning-hub/units", window.origin);
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
     * Add the Mailerlite scripts to the Head
     */
    private function setupMailerlite() {
        add_action('wp_head', function() {
        ?>
            <script>
                (function(m,a,i,l,e,r){ m['MailerLiteObject']=e;function f(){
                var c={ a:arguments,q:[]};var r=this.push(c);return "number"!=typeof r?r:f.bind(c.q);}
                f.q=f.q||[];m[e]=m[e]||f.bind(f.q);m[e].q=m[e].q||f.q;r=a.createElement(i);
                var _=a.getElementsByTagName(i)[0];r.async=1;r.src=l+'?v'+(~~(new Date().getTime()/1000000));
                _.parentNode.insertBefore(r,_);})(window, document, 'script', 'https://static.mailerlite.com/js/universal.js', 'ml');

                var ml_account = ml('accounts', '2344751', 'd5t1g6z7h7', 'load');
            </script>
        <?php
        });
    }

    /*
     * Add Google analytics script
     */
    private function setupGoogleAnalytics() {
        add_action('wp_head', function() {
        ?>
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=G-LLS9GKDT7C"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', 'G-LLS9GKDT7C');
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

    /*
     * Check login for workshop onboarding pages
     */
    public function onboardingLoginCheck() {
        add_action('template_redirect', function() {
            if (!is_user_logged_in() && (is_page('workshop-onboarding') || is_page(9302) || is_page(9304) || is_page(9306))) {
                auth_redirect();
            } 
        });
    }
}

$alfresco = new Alfresco();