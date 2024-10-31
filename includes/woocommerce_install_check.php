<?php

if (!class_exists('RTS_WooTracking_Api_Dependency_Check'))
{
    class RTS_WooTracking_Api_Dependency_Check
    {

        static function getMissingDependencies()
        {
            if(is_multisite()){			
			     $active_plugins = apply_filters('active_plugins', get_site_option('active_sitewide_plugins'));
			}else{
            	$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
			}	

            $dependencies = array(
                'woocommerce/woocommerce.php' => array(
                    "message"   => "<a href='http://wordpress.org/extend/plugins/woocommerce/'>WooCommerce</a>",
                    "min_version"   => "2.2"
                ),
                'woocommerce-shipment-tracking/shipment-tracking.php' => array(
                    "message" => "<a href='https://www.woothemes.com/products/shipment-tracking/'>WooCommerce Shipment Tracking</a>",
                    "min_version"   => "1.4.2",
                    "alternates"    => ['woocommerce-shipment-tracking/woocommerce-shipment-tracking.php','woocommerce-shipment-tracking 2/woocommerce-shipment-tracking.php']
                )
            );

            $missingDependencies = array();

            foreach ($dependencies as $dependency => $info)
            {
                
			    if (!in_array($dependency, $active_plugins) && !array_key_exists($dependency, $active_plugins))
                {
                    if (isset($info["alternates"]))
                    {
                        $isAltExist = false;
						foreach ($info["alternates"] as $alternate)
                        {
                        	   
						    if (in_array($alternate, $active_plugins) || array_key_exists($alternate, $active_plugins))
                            {
                               							   
							    // continue on to check version
                                $dependency = $alternate;
								$isAltExist = true;
                                break;
                            }
                        }
						if(!$isAltExist){
							$missingDependencies[$dependency] = $info["message"] . " version " . $info["min_version"] . " or greater.";
							continue;
						}
                    }
                    else
                    {
                        $missingDependencies[$dependency] = $info["message"] . " version " . $info["min_version"] . " or greater.";
                        continue;
                    }
                }

                // plugin is installed, but check for version
                $pluginData = get_plugin_data(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $dependency);
                $versionDiff = version_compare($info["min_version"], $pluginData["Version"]);

                if ($info["min_version"] != $pluginData["Version"] && $versionDiff > 0)
                {
                    $missingDependencies[$dependency] = $info["message"] . " version " . $info["min_version"] . " or greater. (you have version {$pluginData['Version']})";
                }

            }

            return $missingDependencies;
        }

        static function hasAllDependencies()
        {
            return count(RTS_WooTracking_Api_Dependency_Check::getMissingDependencies()) == 0;
        }

        static function adminNotices()
        {
            if (!RTS_WooTracking_Api_Dependency_Check::hasAllDependencies())
            {
                $pluginData = get_plugin_data(RTS_WOOTRACKING_API_PLUGIN_FILE);

                $error_message = "{$pluginData['Name']} requires the following plugins to be installed and activated: " .
                    "<ul><li>" .

                    implode("</li><li>", array_values(RTS_WooTracking_Api_Dependency_Check::getMissingDependencies()))

                . "</li>" .
                "Tracking API endpoints will be disabled until these errors are corrected.";

                echo "<div class='error'><p>$error_message</p></div>";

            }
        }
    }
}

add_action('admin_notices', 'RTS_WooTracking_Api_Dependency_Check::adminNotices');