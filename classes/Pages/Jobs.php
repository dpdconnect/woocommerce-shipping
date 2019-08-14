<?php

namespace DpdConnect\classes\Pages;

use DpdConnect\classes\JobList;

class Jobs
{
    public static function options()
    {
        global $jobsTable;

        $option = 'per_page';
        $args = [
            'label' => 'Jobs',
            'default' => 10,
            'option' => 'jobs_per_page'
        ];

        add_screen_option($option, $args);
        $jobTable = new JobList();
    }

    public static function render()
    {
        $jobTable = new JobList();

        $batchId = null;

        if (isset($_GET['batchId'])) {
            $batchId = (int) $_GET['batchId'];
        }

        if (is_null($batchId)) {
            echo '</pre><div class="wrap"><h2>' . __('Job overview', 'dpdconnect') . '</h2>';
        } else {
            echo '</pre><div class="wrap"><h2>' . __('Job overview batch ', 'dpdconnect') . $batchId . '</h2>';
        }

        $jobTable->prepare_items();
        ?>
        <form method="post">
            <input type="hidden" name="page" value="dpdconnect-jobs" />
        </form>
        <?php
        $jobTable->display();

        echo '</form></div>';
    }
}
