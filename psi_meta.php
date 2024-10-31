<?php
/**************************************************************************

Plugin Name:  PSI Meta Tags
Plugin URI:   http://www.profilingsolutions.com/software-products/wordpress-plugins/Site-Page-Meta-Tags/
Description:  Set site-wide Meta tags for Keywords and Descriptions; Append-to or Replace these tags on any page or post.
Version:      1.0RC
Author:       Matt Poer, Profiling Solutions <mpoer@profilingsolutions.com>
Author URI:   http://www.profilingsolutions.com

**************************************************************************/

function psi_meta_conf(){
    add_options_page(
        __('Meta Tags Options', 'add-meta-tags'),
        __('Meta Tags', 'add-meta-tags'), 
        8,
        __FILE__,
        'psi_meta_options_page'
    );
}

function psi_meta_options_page(){
    if(isset($_POST['psi_meta_options'])){
        update_option('psi_meta_options',$_POST['psi_meta_options']);
        echo "<h2 style='color:red;'>Options Updated, thanks</h2>";
    }

    $thispage = $_SERVER['REQUEST_URI'];
    $psi_meta_options = get_option('psi_meta_options');
    echo <<<EOECHO
    <div class="wrap">
        <h2>Add Meta Tags</h2>
        <p>This is where you can configure the Add-Meta-Tags plugin and read about how the plugin adds META tags in the WordPress pages.</p>
    </div>

    <div class="wrap">
        <form name="psi_meta_options[]" method="post" action="$thispage">
            <p>This is the text of your website description:</p>
            <textarea name='psi_meta_options[site_description]' cols="80" rows="5">$psi_meta_options[site_description]</textarea>

            <p>This field will list your keywords. Use commas to separate keywords, for example:
                <blockquote style="font-weight:bold;">dogs, puppies, adoptions, pets</blockquote></p>

            <textarea name='psi_meta_options[site_keywords]' cols="80" rows="5">$psi_meta_options[site_keywords]</textarea>
            <br><br>
            <input type="submit" value="Update Meta Tags" />
        </form>
    </div>
EOECHO;
}

function post_form_additional_options(){
    $psi_meta_options = get_option('psi_meta_options');
    global $post;
    $psimeta = get_post_meta($post->ID,'psi_meta_options',true);

    // I wish that I had found a more elegant way to detect the selected value and automatically
    // re-select it, but I didn't. I did it with this switch/case for description, then I did
    // it again for keywords.
    switch($psimeta['post_description_opt']){
        case "Append":
            $description_options = "
            <option value='Default'>Use Default Description</option>
            <option value='Append' selected>Append My Description</option>
            <option value='Replace'>Replace with My Description</option>";
            break;
        case "Replace":
            $description_options = "
            <option value='Default'>Use Default Description</option>
            <option value='Append'>Append My Description</option>
            <option value='Replace' selected>Replace with My Description</option>";
            break;
        default:
            $description_options = "
            <option value='Default' selected>Use Default Description</option>
            <option value='Append'>Append My Description</option>
            <option value='Replace'>Replace with My Description</option>";
            break;
    }
    switch($psimeta['post_keywords_opt']){
        case "Append":
            $keyword_options = "
            <option value='Default' selected>Use Default Keywords</option>
            <option value='Append' selected>Append My Keywords</option>
            <option value='Replace'>Replace with My Keywords</option>";
            break;
        case "Replace":
            $keyword_options = "
            <option value='Default' selected>Use Default Keywords</option>
            <option value='Append'>Append My Keywords</option>
            <option value='Replace' selected>Replace with My Keywords</option>";
            break;
        default:
            $keyword_options = "
            <option value='Default' selected>Use Default Keywords</option>
            <option value='Append'>Append My Keywords</option>
            <option value='Replace'>Replace with My Keywords</option>";
            break;
    }
    $form = <<<EOFORM
    <h2>Meta Description</h2>
    <p>Append or replace the description?</p>
            <select name="psi_meta_options[post_description_opt]">
                $description_options
            </select><br>
    <p>The current site-wide description is:
        <blockquote style="font-weight:bold;">$psi_meta_options[site_description]</blockquote></p>
    <textarea name='psi_meta_options[post_description]' cols="80" rows="5">$psimeta[post_description]</textarea>

    <h2>Meta Keywords</h2>
    <p>Append or replace the keywords?</p>
            <select name="psi_meta_options[post_keywords_opt]">
                $keyword_options
            </select><br>
    <p>The current site-wide keywords are:
        <blockquote style="font-weight:bold;">$psi_meta_options[site_keywords]</blockquote></p>
    <textarea name='psi_meta_options[post_keywords]' cols="80" rows="5">$psimeta[post_keywords]</textarea>
EOFORM;
    echo $form;
    echo '<input type="hidden" name="psi_meta_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
}

function psi_add_meta_boxen(){
    // add the extra form elements to Posts and Pages
    add_meta_box('psi_meta_additional_options','Meta Options', post_form_additional_options, 'post');
    add_meta_box('psi_meta_additional_options','Meta Options', post_form_additional_options, 'page');
    add_action('save_post','psi_post_meta_save');
}

function psi_post_meta_save($post_id){
    // authentication checks

    // make sure data came from our meta box
    if (!wp_verify_nonce($_POST['psi_meta_noncename'],__FILE__)) return $post_id;

    // check user permissions
    if ($_POST['post_type'] == 'page') {
        if (!current_user_can('edit_page', $post_id)) return $post_id;
    } else {
        if (!current_user_can('edit_post', $post_id)) return $post_id;
    }

    // authentication passed, save data

    $current_data = get_post_meta($post_id, 'psi_meta_options', TRUE);
    $new_data = $_POST['psi_meta_options'];

	if ($current_data) {
		if (is_null($new_data)) delete_post_meta($post_id,'psi_meta_options');
		else update_post_meta($post_id,'psi_meta_options',$new_data);
	} elseif (!is_null($new_data)) {
		add_post_meta($post_id,'psi_meta_options',$new_data,TRUE);
	}

	return $post_id;
}

function psi_meta_display(){
    // grab the site-wide defaults
    $psi_meta_options = get_option('psi_meta_options');
    $keywords = $psi_meta_options['site_keywords'];
    $description = $psi_meta_options['site_description'];

    // if this is a single blog post or a page, go deeper
    if(is_singular()){
        global $post;
        $psimeta = get_post_meta($post->ID,'psi_meta_options',true);
        switch($psimeta['post_keywords_opt']){
            case "Append":
                $keywords .= $psimeta['post_keywords'];
                break;
            case "Replace":
                $keywords = $psimeta['post_keywords'];
                break;
            default:
                break;
        }
        switch($psimeta['post_description_opt']){
            case "Append":
                $description .= $psimeta['post_description'];
                break;
            case "Replace":
                $description = $psimeta['post_description'];
                break;
            default:
                break;
        }
    }

    // output the meta tags
    echo "<meta name='keywords' content='$keywords' />\n";
    echo "<meta name='description' content='$description' />\n";

    return true;
}

// show the configuration menu
add_action('admin_menu', 'psi_meta_conf');

// hook into the page
add_action('wp_head', 'psi_meta_display', 0);

// hook the additional form boxen for posts & pages
add_action('admin_init', 'psi_add_meta_boxen');
?>