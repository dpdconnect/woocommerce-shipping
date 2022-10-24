<?php

namespace DpdConnect\classes\Pages;

use DpdConnect\classes\BatchList;

class Batches
{
    public static function options()
    {
        global $batchTable;

        $option = 'per_page';
        $args = [
            'label' => 'Batches',
            'default' => 10,
            'option' => 'batches_per_page'
        ];

        add_screen_option($option, $args);
        $batchTable = new BatchList();
    }

    public static function render()
    {
        $batchTable = new BatchList();
        echo '</pre><div class="wrap"><h2>' . __('Batch overview', 'dpdconnect') . '</h2>';

        $batchTable->prepare_items();
        ?>
        <form method="post">
            <input type="hidden" name="page" value="dpdconnect-batches" />
        </form>
        <?php
        $batchTable->display();

        echo '</form></div>';
    }
}
