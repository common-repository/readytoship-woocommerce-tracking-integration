<?php

if (!function_exists('rts_wootracking_api_all_dependencies'))
{
    function rts_wootracking_api_all_dependencies()
    {
        if (!class_exists('RTS_WooTracking_Api_Dependency_Check'))
        {
            return false;
        }
        return RTS_WooTracking_Api_Dependency_Check::hasAllDependencies();
    }
}