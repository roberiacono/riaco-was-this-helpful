
// Aggiungere una pagina delle impostazioni per selezionare il caricamento degli script
function ri_wth_add_settings_page() {
    add_options_page(
        __('Helpful Feedback Settings', 'ri-wth-feedback'),
        __('Helpful Feedback', 'ri-wth-feedback'),
        'manage_options',
        'ri-wth-settings',
        'ri_wth_render_settings_page'
    );
}
add_action('admin_menu', 'ri_wth_add_settings_page');

function ri_wth_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Helpful Feedback Settings', 'ri-wth-feedback'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ri-wth-settings-group');
            do_settings_sections('ri-wth-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function ri_wth_register_settings() {
    register_setting('ri-wth-settings-group', 'ri_wth_load_styles');
    register_setting('ri-wth-settings-group', 'ri_wth_load_scripts');

    add_settings_section(
        'ri-wth-settings-section',
        __('Load Settings', 'ri-wth-feedback'),
        'ri_wth_settings_section_callback',
        'ri-wth-settings'
    );

    add_settings_field(
        'ri_wth_load_styles',
        __('Load Styles', 'ri-wth-feedback'),
        'ri_wth_load_styles_callback',
        'ri-wth-settings',
        'ri-wth-settings-section'
    );

    add_settings_field(
        'ri_wth_load_scripts',
        __('Load Scripts', 'ri-wth-feedback'),
        'ri_wth_load_scripts_callback',
        'ri-wth-settings',
        'ri-wth-settings-section'
    );
}
add_action('admin_init', 'ri_wth_register_settings');

function ri_wth_settings_section_callback() {
    echo __('Select whether to load the plugin styles and scripts.', 'ri-wth-feedback');
}

function ri_wth_load_styles_callback() {
    $option = get_option('ri_wth_load_styles');
    echo '<input type="checkbox" name="ri_wth_load_styles" value="1"' . checked(1, $option, false) . '>';
}

function ri_wth_load_scripts_callback() {
    $option = get_option('ri_wth_load_scripts');
    echo '<input type="checkbox" name="ri_wth_load_scripts" value="1"' . checked(1, $option, false) . '>';
}