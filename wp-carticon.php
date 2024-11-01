<?php
/*
Plugin Name: WP Carticon
Plugin URI:  https://carticon.com
Description: Quickly add Carticon widget on your Wordpress site and start selling online instantly
Version:     1.0.0
Author:      Carticon 
*/


// Plugin folder path
if ( ! defined( 'WP_CARTICON_PLUGIN_DIR' ) ) {
    define( 'WP_CARTICON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin folder URL
if ( ! defined( 'WP_CARTICON_PLUGIN_URL' ) ) {
    define( 'WP_CARTICON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin root file
if ( ! defined( 'WP_CARTICON_PLUGIN_FILE' ) ) {
    define( 'WP_CARTICON_PLUGIN_FILE', __FILE__ );
}


/**
 * Register the plugin menu section
 */
function carticon_register_settings()
{
    register_setting('carticon_options', 'carticon_js_script', array('type' => 'string'));
    register_setting('carticon_options', 'carticon_activate_global', array('type' => 'boolean'));
    add_option('carticon_js_script');
    add_option('carticon_activate_global');
}
add_action('admin_init', 'carticon_register_settings');


/**
 * Adds Carticon settings page in the admin menu
 */
function carticon_register_options_page()
{
    add_options_page('Carticon', 'Carticon', 'manage_options', 'carticon-settings', 'carticon_options_page');
}
add_action('admin_menu', 'carticon_register_options_page');


/**
* Include custom scripts and styles
*/
function carticon_enqueue_scripts() 
{
    wp_enqueue_style("admin-css", WP_CARTICON_PLUGIN_URL . "assets/css/admin.css");

}
add_action( 'admin_enqueue_scripts', 'carticon_enqueue_scripts' );


/**
 * The Carticon settings page
 */
function carticon_options_page()
{
    ?>
    <div>
        <div><img src="<?php echo WP_CARTICON_PLUGIN_URL; ?>assets/img/carticon.png" class="carticon_logo">
             </img></div>
        
        <div>
            <p>Start selling today with your Carticon widget. <a href="https://admin.carticon.com/?invite=WC5XQT0018" target="_blank">Register or manage your shop in the Carticon Control Panel</a>.</p>
        </div>

        <form method="post" action="options.php">
            <?php
                settings_fields('carticon_options');
                do_settings_sections('carticon-settings');
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">Your Carticon code</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Your Carticon code</span></legend>
                                <textarea name="carticon_js_script" rows="10" cols="50" id="carticon_js_script" class="large-text code"><?php echo get_option('carticon_js_script'); ?></textarea>
                                <p class="description">Copy the Javascript code from your Carticon account in the form above, to activate your Wordpress shop.</p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Activate shop</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Activate shop</span></legend>
                                <label for="carticon_activate_global">
                                    <input name="carticon_activate_global" type="checkbox" value="1" id="carticon_activate_global" <?php echo checked( 1, get_option('carticon_activate_global'), false ); ?>">
                                    Display the Carticon shop widget on all pages of your site
                                </label>
                                <p class="description">If you only want to enable it on a specific page or pages, you can use the <strong>shortcode</strong> or enable it on the edit screen of any page, using the <strong>Page Attributes</strong> section.</p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            <table>
                <?php submit_button(); ?>
        </form>
     
        <div>
            <h2>The shortcode</h2>
            <p>Use this shortcode to display the Carticon widget on a specific page: <code>[carticon_shop]</code>.</p>
            <p>Or simply set any page/post as <strong>Shop front</strong>, in the <strong>Page attributes</strong> section (right sidebar).
        <div>
        <div>
            <h2>Got any questions?</h2>
            <p><a href="https://admin.carticon.com/?invite=WC5XQT0018" target="_blank">Carticon Control Panel</a></p>
            <p><a href="https://carticon.com?invite=WC5XQT0018" target="_blank">Support & documentation</a></p>
        </div>

    </div>
    <?php 
}


/**
 * The Carticon shortcode
 */
function carticon_javascript_code()
{
    $result = get_option('carticon_js_script');
    return $result;
}
add_shortcode('carticon_shop', 'carticon_javascript_code');


/**
 * Adds the meta box to the page screen
 */
function carticon_add_meta_box()
{
    add_meta_box(
        'carticon-meta-box', // id, used as the html id att
        __('Carticon'), // meta box title, like "Page Attributes"
        'carticon_meta_box_cb', // callback function, spits out the content
        ['page', 'post'], // post type or page.
        'side', // context (where on the screen
        'low' // priority, where should this go in the context?
    );
}
add_action('add_meta_boxes', 'carticon_add_meta_box');


/**
 * Callback function for our meta box.  Echos out the content
 */
function carticon_meta_box_cb($post)
{
    $values = get_post_custom( $post->ID );
    $check = isset( $values['carticon_activate_on_post'] ) ? esc_attr( $values['carticon_activate_on_post'][0] ) : '';

    // We'll use this nonce field later on when saving.
    wp_nonce_field( 'carticon_on_nonce', 'meta_box_nonce' );
    ?>
    <p>
        <label for="carticon_activate_on_post">
            <input type="checkbox" id="carticon_activate_on_post" name="carticon_activate_on_post" <?php checked( $check, 'on' ); ?> />
            Make this page a shop front
        </label>
    </p>
    <?php
}


/**
 * Saves the Carticon meta box settings
 */
function carticon_meta_save( $post_id )
{
    // Bail if we're doing an auto save
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // if our nonce isn't there, or we can't verify it, bail
    if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'carticon_on_nonce' ) ) return;

    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_post' ) ) return;

    // This is purely my personal preference for saving check-boxes
    $chk = isset( $_POST['carticon_activate_on_post'] ) && $_POST['carticon_activate_on_post'] ? 'on' : 'off';
    update_post_meta( $post_id, 'carticon_activate_on_post', $chk );
}
add_action( 'save_post', 'carticon_meta_save' );


/**
 * Adds the Carticon code if enabled globally or on a specific post
 */
function carticon_inject_snippet() {
    $inject_code = false;
    if (intval(get_option('carticon_activate_global'))) {
        $inject_code = true;
    }
    if (!$inject_code) {
        $values = get_post_custom( get_the_ID() );
        $check = isset( $values['carticon_activate_on_post'] ) ? esc_attr( $values['carticon_activate_on_post'][0] ) : false;
        if ($check == "on") {
            $inject_code = true;
        }
    }
    if ($inject_code) {
        print get_option('carticon_js_script');
    }
}
add_action('wp_head', 'carticon_inject_snippet');
