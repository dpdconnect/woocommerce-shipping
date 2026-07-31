<?php

namespace DpdConnect\classes;

use DpdConnect\classes\Database\Label;
use DpdConnect\classes\Handlers\Download;
use DpdConnect\classes\Handlers\LabelRequest;

class Router
{
    /** Capability required to download a shipping label. */
    const DOWNLOAD_CAPABILITY = 'manage_woocommerce';

    /** Nonce action prefix; the label id is appended so a link is bound to one label. */
    const NONCE_ACTION = 'dpdconnect_download_label';

    /**
     * Build an authenticated shipping-label download URL for the given label id.
     * The nonce binds the link to the current user and to this specific label,
     * so it cannot be reused by an anonymous visitor or for another order.
     */
    public static function labelUrl($id): string
    {
        $url = add_query_arg(
            ['plugin' => 'dpdconnect', 'file' => 'shipping_label', 'id' => $id],
            admin_url()
        );

        return wp_nonce_url($url, self::NONCE_ACTION . '_' . $id);
    }

    /**
     * Registered on the `init` hook (not at plugin load time) so that the
     * pluggable auth functions used below are available.
     */
    public static function init()
    {
        $parameters = $_GET;

        if (!isset($parameters['plugin']) || $parameters['plugin'] !== 'dpdconnect') {
            return;
        }

        if (isset($parameters['file']) && $parameters['file'] === 'shipping_label') {
            self::downloadShippingLabel($parameters);
            return;
        }

        if (isset($parameters['page']) && $parameters['page'] === 'dpdconnect_print_shipment_label') {
            add_action('woocommerce_after_register_post_type', [LabelRequest::class, 'single']);
        }
    }

    private static function downloadShippingLabel(array $parameters): void
    {
        $id = isset($parameters['id']) ? $parameters['id'] : '';

        // Only shop managers may download labels.
        if (!current_user_can(self::DOWNLOAD_CAPABILITY)) {
            wp_die(
                esc_html__('You are not allowed to download this label.', 'dpdconnect'),
                '',
                ['response' => 403]
            );
        }

        // The link must carry a valid, label-specific nonce.
        $nonce = isset($parameters['_wpnonce']) ? $parameters['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION . '_' . $id)) {
            wp_die(
                esc_html__('This label download link is invalid or has expired.', 'dpdconnect'),
                '',
                ['response' => 403]
            );
        }

        $label = (new Label())->get($id);
        if (!$label) {
            wp_die(
                esc_html__('Label not found.', 'dpdconnect'),
                '',
                ['response' => 404]
            );
        }

        Download::pdf($label['contents'], $label['shipment_identifier']);
        exit;
    }
}
