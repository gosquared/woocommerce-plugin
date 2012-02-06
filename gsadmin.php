<?

add_option('gstc_acct');
add_option('gstc_trackAdmin');
add_option('gstc_trackPreview');
add_option('gstc_trackUser');
add_action('admin_init', 'gs_init');
add_action('admin_menu', 'gs_options');

function gs_options() {
    $page = add_options_page('GoSquared', 'GoSquared', 'manage_options', 'gs-livestats', 'gs_options_page');
    /* Using registered $page handle to hook stylesheet loading */
    add_action('admin_print_styles-' . $page, 'gs_admin_style');
}

function gs_admin_style() {
    wp_enqueue_style('gs_style');
}

function gs_success($message) {
    echo '<div class="center"><div class="message_wrapper"><div class="gs_success">' . $message . '</div></div></div>';
}

function gs_fail($message) {
    echo '<div class="center"><div class="message_wrapper"><div class="gs_fail">' . $message . '</div></div></div>';
}

function gs_warn($message) {
    echo '<div class="center"><div class="message_wrapper"><div class="gs_warn">' . $message . '</div></div></div>';
}

function gs_options_page() {
    global $style_file, $style_url;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>

<div id="gs-admin-settings-page">
    <br />


    <a href="http://www.gosquared.com/" title="Go to the GoSquared homepage" target="_blank"><div id="gosquaredlogo"></div></a>

    <?php
    if (isset($_POST['gs_acct'])) {
        // Handle submission
        $acct = $_POST['gs_acct'];
        $trackAdmin = isset($_POST['gs_trackAdmin']) ? $_POST['gs_trackAdmin'] : 'Yes';
        $trackPreview = isset($_POST['gs_trackPreview']) ? $_POST['gs_trackPreview'] : "Yes";
        $trackUser = isset($_POST['gs_trackUser']) ? $_POST['gs_trackUser'] : 'Username';
        $valid_acct = preg_match('/^GSN-[0-9]{6,7}-[A-Z]{1}$/', $acct);
        if ($valid_acct) {
            update_option('gstc_acct', $acct);
            update_option('gstc_trackAdmin', $trackAdmin);
            update_option('gstc_trackPreview', $trackPreview);
            update_option('gstc_trackUser', $trackUser);
            gs_success('Settings updated successfully');
        } else {
            $msg = "";
            if (!$valid_acct)
                $msg .= '<p>Site token not of valid format. Must be like GSN-000000-X</p>';
            if(!$msg) $msg = 'An error occurred';
            gs_fail($msg);
        }
    }

    if (!woocommerce_installed()) {
        $msg = "WooCommerce Plugin is not installed";
        gs_fail($msg);
    }

    $acct = get_option('gstc_acct');
    $trackAdmin = get_option('gstc_trackAdmin');
    $trackPreview = get_option('gstc_trackPreview');
    $trackUser = get_option('gstc_trackUser');
    ?>

    <div class="gs-admin-header">

        <?php
        if (!$acct)
            $default_text = 'GSN-000000-X';
        else
            $default_text = $acct;

        if (!$trackAdmin)
            $trackAdmin = 'Yes';

        if (!$trackPreview)
            $trackPreview = 'Yes';

        if (!$trackUser)
            $trackUser = 'Username';
        ?>

    </div>

    <form name="gs-options" action="" method = "post">

        <h2>Site Token - Start tracking "<?php echo get_bloginfo('name'); ?>" with GoSquared.</h2>

        <p>Your Site Token enables GoSquared to monitor your Wordpress site's traffic. <a href="https://www.gosquared.com/join/" title="Sign up to GoSquared for free to start monitoring your site in real-time" target="_blank">Sign up for free</a> to register your site with GoSquared.</p>


        <div class="input-field">
            <span class="input-label">Your GoSquared Site Token </span>
            <input class="gs-text-input" type="text" name="gs_acct" value = "<?php echo $default_text ?>"
                   onclick="if(this.value=='<?php echo $default_text ?>')this.value=''"
                   onblur="if(this.value=='')this.value='<?php $default_text ?>'"/>&nbsp;
            <a href="http://www.gosquared.com/support/wiki/faqs#faq-site-token" target="_blank">What's this?</a>
        </div>

        <h2>Advanced Settings</h2>
        <table class="gs-settings">
            <tr>
                <td class="label">Track admin pages </td>
                <td><input type="radio" name="gs_trackAdmin" value="Yes" id="trackAdmin" <?php if ($trackAdmin == 'Yes')
                    echo 'checked="checked" '; ?>/> Yes</td>
                <td><input type="radio" name="gs_trackAdmin" value="No" id="trackAdmin" <?php if ($trackAdmin == 'No')
                    echo 'checked="checked" '; ?>/> No</td>
            </tr>
            <tr>
                <td class="label">Track post preview pages</td>
                <td><input type="radio" name="gs_trackPreview" value="Yes" id="trackPreview" <?php if ($trackPreview == 'Yes')
                    echo 'checked="checked" '; ?>/> Yes</td>
                <td><input type="radio" name="gs_trackPreview" value="No" id="trackPreview" <?php if ($trackPreview == 'No')
                    echo 'checked="checked" '; ?>/> No</td>
            </tr>
            <tr>
                <td class="label">Tag individual users with </td>
                <td><input type="radio" name="gs_trackUser" value="Off" id="trackUser" <?php if ($trackUser == 'Off')
                    echo 'checked="checked" '; ?>/> Off</td>
                <td><input type="radio" name="gs_trackUser" value="UserID" id="trackUser" <?php if ($trackUser == 'UserID')
                    echo 'checked="checked" '; ?>/> User ID</td>
                <td><input type="radio" name="gs_trackUser" value="Username" id="trackUser" <?php if ($trackUser == 'Username')
                    echo 'checked="checked" '; ?> /> Username</td>
                <td class="wide"><input type="radio" name="gs_trackUser" value="DisplayName" id="trackUser" <?php if ($trackUser == 'DisplayName')
                    echo 'checked="checked" '; ?>/> Display Name</td>
            </tr>
        </table>
        <input type="submit" value="Save Settings" class="button-primary" />
    </form>
</div>

<?php
}
