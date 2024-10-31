<?php
if (!defined('ABSPATH'))
{
    exit; // Exit if accessed directly
}

class WC_API_Tracking extends WC_API_Resource
{

    /** @var string $post_type the custom post type */
    protected $post_type = 'shop_order';

    /** @var  WC_Shipment_Tracking_Actions */
    protected $wcShipmentTracking;

    protected $base = '/orders';

    /**
     * WC_API_Tracking constructor.
     */
    public function __construct($server)
    {
        parent::__construct($server);
        $this->wcShipmentTracking = WC_Shipment_Tracking_Actions::get_instance();
        $this->initHooks();
    }

    public function initHooks()
    {
        add_filter('woocommerce_api_index', array($this, 'addDataToApiIndex'));
    }

    public function addDataToApiIndex($data)
    {
        $data["store"]["meta"]["readytoship_tracking_api_installed"] = true;
        return $data;
    }

    /**
     * Register the routes for this class
     *
     * GET /order/<id>/tracking
     * POST /order/<id>/tracking
     *
     * @since 2.1
     * @param array $routes
     * @return array
     */
    public function register_routes($routes)
    {
        # GET/POST /orders/<id>/tracking
        $routes[$this->base . '/(?P<order_id>\d+)/tracking'] = array(
            array(array($this, 'get_tracking'), WC_API_Server::READABLE),
            array(array($this, 'create_tracking'), WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA),
            array(array($this, 'delete_tracking'), WC_API_Server::DELETABLE | WC_API_Server::ACCEPT_DATA)
        );

        return $routes;
    }

    /**
     * Get the tracking for an order
     *
     * @since 2.1
     * @param string $order_id order ID
     * @return array
     */
    public function get_tracking($order_id)
    {
        // ensure ID is valid order ID
        $order_id = $this->validate_request($order_id, $this->post_type, 'read');

        if (is_wp_error($order_id))
        {
            return $order_id;
        }

        $trackingItems = $this->wcShipmentTracking->get_tracking_items($order_id);

        return array('tracking' => apply_filters('woocommerce_api_tracking_response', $trackingItems, $order_id));
    }

    public function create_tracking($order_id, $data)
    {
        try
        {
            if (!isset($data['tracking']))
            {
                throw new WC_API_Exception('woocommerce_api_missing_tracking_data', sprintf(__('No %1$s data specified to edit %1$s', 'woocommerce'), 'tracking'), 400);
            }

            $data = $data['tracking'];

            $order_id = $this->validate_request($order_id, $this->post_type, 'edit');

            if (is_wp_error($order_id))
            {
                return $order_id;
            }

            foreach (array("provider", "tracking_number") as $required)
            {
                if (!isset($data[$required]))
                {
                    throw new WC_API_Exception('woocommerce_api_invalid_' . $required, sprintf(__('%s is required.', 'woocommerce'), ucfirst($required)), 400);
                }
            }

            $tracking_data = array(
                'tracking_provider' => '',
                'custom_tracking_provider' => '',
                'custom_tracking_link' => '',
                'tracking_number' => $data["tracking_number"],
                'date_shipped' => ''
            );

            $knownProvider = false;
            foreach ($this->wcShipmentTracking->get_providers() as $country => $providers)
            {
                foreach ($providers as $title => $url)
                {
                    if ($data['provider'] == $title)
                    {
                        $knownProvider = true;
                    }
                }
            }

            if ($knownProvider)
            {
                $tracking_data['tracking_provider'] = sanitize_title($data['provider']);
            }
            else
            {
                $tracking_data['custom_tracking_provider'] = sanitize_title($data['provider']);
                if (isset($data['tracking_link']))
                {
                    $tracking_data['custom_tracking_link'] = $data['tracking_link'];
                }
            }

            foreach ($this->wcShipmentTracking->get_tracking_items($order_id) as $existingItem)
            {
                if ($existingItem['tracking_number'] == $tracking_data['tracking_number'])
                {
                    throw new WC_API_Exception('woocommerce_api_duplicate_tracking_number', __('Tracking number already exists on this order.', 'woocommerce'), 400);
                }
            }

            $trackingItem = $this->wcShipmentTracking->add_tracking_item($order_id, $tracking_data);

            $this->server->send_status(201);

            return $trackingItem;

        } catch (WC_API_Exception $e)
        {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), array('status' => $e->getCode()));
        }

    }

    public function delete_tracking($order_id, $data)
    {
        try
        {
            if (!isset($data['tracking']))
            {
                throw new WC_API_Exception('woocommerce_api_missing_tracking_data', sprintf(__('No %1$s data specified to delete %1$s', 'woocommerce'), 'tracking'), 400);
            }

            $data = $data['tracking'];

            $order_id = $this->validate_request($order_id, $this->post_type, 'edit');

            if (is_wp_error($order_id))
            {
                return $order_id;
            }

            foreach ($this->wcShipmentTracking->get_tracking_items($order_id) as $existingItem)
            {
                if ($existingItem['tracking_number'] == $data['tracking_number'])
                {
                    $this->wcShipmentTracking->delete_tracking_item($order_id, $existingItem['tracking_id']);
                    $this->server->send_status( '202' );
                    return array( 'message' => sprintf( __( 'Deleted %s data.', 'woocommerce' ), 'tracking' ) );
                }
            }

            throw new WC_API_Exception("woocommerce_api_tracking_data_not_found", sprintf(__('Tracking data not found on order.')));

        } catch (WC_API_Exception $e)
        {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), array('status' => $e->getCode()));
        }
    }
}