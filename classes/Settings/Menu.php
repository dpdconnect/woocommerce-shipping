<?php

namespace DpdConnect\classes\Settings;

use DpdConnect\classes\JobView;
use DpdConnect\classes\Pages\FreshFreeze;
use DpdConnect\classes\Pages\Jobs;
use DpdConnect\classes\Pages\Batches;

class Menu
{
    public static function handle()
    {
        add_action('admin_menu', [self::class, 'render']);
        add_filter('submenu_file', [self::class, 'filter']);
    }

    public static function render()
    {
        // add top level menu page
        add_menu_page(
            'DPD Connect',
            'DPD Connect',
            'manage_woocommerce',
            'dpdconnect',
            [self::class, 'page'],
            plugin_dir_url(__FILE__) . '../../assets/images/icon-dpd.png'
        );

        // add sub menu page
        add_submenu_page(
            'dpdconnect',
            'settings',
            __('Settings', 'dpdconnect'),
            'manage_woocommerce',
            'dpdconnect',
            [self::class, 'page']
        );

        // add sub menu page
        $hook = add_submenu_page(
            'dpdconnect',
            'DPD batches',
            __('Batches', 'dpdconnect'),
            'manage_options',
            'dpdconnect-batches',
            [Batches::class, 'render']
        );
        add_action("load-$hook", [Batches::class, 'options']);

        // add sub menu page
        $hook = add_submenu_page(
            'dpdconnect',
            'DPD jobs',
            __('Jobs', 'dpdconnect'),
            'manage_options',
            'dpdconnect-jobs',
            [Jobs::class, 'render']
        );

        add_action("load-$hook", [Jobs::class, 'options']);

        $hook = add_submenu_page(
            'dpdconnect',
            'DPD job details',
            '',
            'manage_options',
            'dpdconnect-job-details',
            function () {
                $jobView = new JobView();
                $jobView->render();
            }
        );

        // Add Fresh and Freeze form page
        add_submenu_page(
            null,
            'DPD Fresh Freeze',
            '',
            'manage_options',
            'dpdconnect-fresh-freeze',
            function () {
                $freshFreeze = new FreshFreeze();
                $freshFreeze->render();
            }
        );
    }

    public static function filter($submenu_file)
    {
        global $plugin_page;

        $hidden_submenus = [
            'dpdconnect-job-details' => true,
        ];

        // Select another submenu item to highlight (optional).
        if ($plugin_page && isset($hidden_submenus[$plugin_page])) {
            $submenu_file = 'job';
        }

        // Hide the submenu.
        foreach ($hidden_submenus as $submenu => $unused) {
            remove_submenu_page('dpdconnect', $submenu);
        }

        return $submenu_file;
    }

    public static function page()
    {
        // check user capabilities
        if (! current_user_can('manage_options')) {
            return;
        }

        /**
         * check if the user have submitted the settings
         * wordpress will add the "settings-updated" $_GET parameter to the url
         */
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('dpdconnect_messages', 'dpdconnect_message', __('Settings Saved', 'dpdconnect'), 'updated');
        }

        // show error/update messages
        settings_errors('dpdconnect_messages');

        /**
         * Fetch the current active tab
         */
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

        ?>

        <div class="wrap">
            <h1><?php echo __('Settings'); ?></h1>
            <form action="options.php" method="post">
            <h2 class="nav-tab-wrapper">
            <a href="?page=dpdconnect&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', 'dpdconnect') ?></a>
            <a href="?page=dpdconnect&tab=credentials" class="nav-tab <?php echo $active_tab == 'credentials' ? 'nav-tab-active' : ''; ?>"><?php echo __('Credentials', 'dpdconnect') ?></a>
                <a href="?page=dpdconnect&tab=company" class="nav-tab <?php echo $active_tab == 'company' ? 'nav-tab-active' : ''; ?>"><?php echo __('Company', 'dpdconnect') ?></a>
                <a href="?page=dpdconnect&tab=product" class="nav-tab <?php echo $active_tab == 'product' ? 'nav-tab-active' : ''; ?>"><?php echo __('Product', 'dpdconnect') ?></a>
                <a href="?page=dpdconnect&tab=parcelshop" class="nav-tab <?php echo $active_tab == 'parcelshop' ? 'nav-tab-active' : ''; ?>"><?php echo __('Parcelshop', 'dpdconnect') ?></a>
                <a href="?page=dpdconnect&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php echo __('Advanced', 'dpdconnect') ?></a>
            </h2>
        <?php

        if ($active_tab === 'general') {
            settings_fields('dpdconnect_general');
            do_settings_sections('dpdconnect_general');
        }

        if ($active_tab === 'credentials') {
            settings_fields('dpdconnect_credentials');
            do_settings_sections('dpdconnect_credentials');
        }

        if ($active_tab === 'company') {
            settings_fields('dpdconnect_company_info');
            do_settings_sections('dpdconnect_company_info');
        }

        if ($active_tab === 'product') {
            settings_fields('dpdconnect_products');
            do_settings_sections('dpdconnect_products');
        }

        if ($active_tab === 'parcelshop') {
            settings_fields('dpdconnect_parcelshop');
            do_settings_sections('dpdconnect_parcelshop');
        }

        if ($active_tab === 'advanced') {
            settings_fields('dpdconnect_advanced');
            do_settings_sections('dpdconnect_advanced');
        }

        /**
         * output setting sections and their fields
         * (sections are registered for "dpdconnect", each field is registered to a specific section)
         * output save settings button
         */
        submit_button(__('Save Settings', 'dpdconnect'));
        ?>
        </form>
        </div>
        <?php
    }
}
