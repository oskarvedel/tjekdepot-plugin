<?php

/**
 * Retrieves depotrum data for a single gd_place.
 *
 * @param int $gd_place_id The ID of the gd_place.
 * @return array An array containing depotrum data for the specified gd_place.
 */

function get_depotrum_data_for_single_gd_place($gd_place_id) {
    $gd_place = pods('gd_place', $gd_place_id);
    $return_array = [];

    // Check if the gd_place exists and has depotrum data
    if ($gd_place && $gd_place->exists() && $gd_place->field('depotrum', true)) {
        // Loop through each depotrum item for the gd_place
        foreach ($gd_place->field('depotrum') as $depotrum_item) {
            $relTypeId = get_post_meta($depotrum_item['ID'], 'rel_type', true);
            
            // Create an array with depotrum data
            $depotrum_data = array(
                'id' => $depotrum_item['ID'],
                'price' => get_post_meta($depotrum_item['ID'], 'price', true),
                'm2' => get_post_meta($relTypeId, 'm2', true),
                'm3' => get_post_meta($relTypeId, 'm3', true),
                'relLokationId' => get_post_meta($depotrum_item['ID'], 'rel_lokation', true),
            );
            
            // Add depotrum data to the return array
            $return_array[] = $depotrum_data;
        }
    }

    return $return_array;
}

/**
 * Counts the number of depotrum units available in the provided depotrum data.
 *
 * @param array $depotrum_data An array containing depotrum data.
 * @return int The total number of depotrum units available.
 */
function find_num_of_units_available($depotrum_data) {
    $counter = 0;
    
    // Loop through each depotrum data item
    foreach ($depotrum_data as $depotrum_data_item) {
        // Increment the counter for each depotrum unit
        ++$counter;
    }
    
    return $counter;
}

/**
 * Calculates the total available units (m2 or m3) based on specified criteria.
 *
 * @param array $depotrum_data An array of depotrum data containing units.
 * @param string $m2_or_m3 Specifies whether to use 'm2' or 'm3'.
 *
 * @return float The total available units based on the specified criteria.
 */
function find_num_of_m2_or_m3_available($depotrum_data, $m2_or_m3) {
    $alladded = 0;

    // Loop through each item in $depotrum_data
    foreach ($depotrum_data as $depotrum_data_item) {
        // Determine which value to use based on $m2_or_m3
        // If $m2_or_m3 is 'm2', add 'm2' to the total; otherwise, add 'm3'
		if ($m2_or_m3 === 'm2' && !empty($depotrum_data_item['m2'])) {
			$alladded += $depotrum_data_item['m2'];
		} elseif ($m2_or_m3 === 'm3' && !empty($depotrum_data_item['m3'])) {
			$alladded += $depotrum_data_item['m3'];
		}
    }

    // Return the total available units
    return round($alladded, 2);
}

/**
 * Calculates the average price based on specified criteria.
 *
 * @param array $depotrum_data An array of depotrum data containing prices and units.
 * @param float $min Minimum value for filtering.
 * @param float $max Maximum value for filtering.
 * @param string $m2_or_m3 Specifies whether to use 'm2' or 'm3'. If empty, uses 'm2'. Leave empty to get average price for whole unit type.
 *
 * @return float The calculated average price based on the specified criteria.
 */
function find_average_price($depotrum_data, $min, $max, $m2_or_m3) {
    // Initialize counters
    $counter = 0;          // Counter for the number of values within the specified range
    $allpricesadded = 0;   // Total prices of values within the specified range
    $allunitsadded = 0;    // Total units (m2 or m3) of values within the specified range

    // Loop through each item in $depotrum_data
    foreach ($depotrum_data as $depotrum_data_item) {
        // Determine which value to use based on $m2_or_m3
        // If $m2_or_m3 is 'm2' or empty, use 'm2'; otherwise, use 'm3'
        $value_to_use = ($m2_or_m3 === 'm2' || empty($m2_or_m3)) ? $depotrum_data_item['m2'] : $depotrum_data_item['m3'];

        // Check if the value is within the specified range
        if ($value_to_use >= $min && $value_to_use <= $max && isset($depotrum_data_item['price']) && !empty($depotrum_data_item['price']) && !empty($value_to_use)) {
            // Add the price and units to the total
            $allpricesadded += $depotrum_data_item['price'];
            $allunitsadded += $value_to_use;

            // Increment the counter
            ++$counter;
        }
    }

    // Return the appropriate average based on $m2_or_m3
    if (empty($m2_or_m3)) {
        // If $m2_or_m3 is empty, calculate and return the average price
        return ($counter > 0) ? round($allpricesadded / $counter, 2) : 0;
    } else {
        // If $m2_or_m3 is specified, calculate and return the average price per unit
        return ($allunitsadded > 0) ? round($allpricesadded / $allunitsadded, 2) : 0;
    }
}

// Schedule the event to run immediately upon activation
register_activation_hook(__FILE__, 'my_activation_function');

function activate_daily_event() {
    // Schedule the event to run immediately
    wp_schedule_event(time(), 'daily', 'update_data_event');
}

// Hook into the scheduled event
add_action('update_data_event', 'update_statistics_data_for_all_gd_places');

function update_statistics_data_for_all_gd_places() {
    // Your code to run once a day
    // For example, get a list of posts and update a custom field value
    $gd_places = get_posts(array('post_type' => 'gd_place', 'posts_per_page' => -1));

    foreach ($gd_places as $gd_place) {
		$depotrum_data = get_depotrum_data_for_single_gd_place_($gd_place->ID);
			       if (!empty($depotrum_data)) {
					   	update_post_meta($gd_place->ID, 'num of units available', find_num_of_units_available($depotrum_data, 0, 1000));
						update_post_meta($gd_place->ID, 'num of m2 available', find_num_of_m2_or_m3_available($depotrum_data, 'm2'));
						update_post_meta($gd_place->ID, 'num of m3 available', find_num_of_m2_or_m3_available($depotrum_data, 'm3'));
					   
						update_post_meta($gd_place->ID, 'average price', find_average_price($depotrum_data, 0, 1000, ''));
						update_post_meta($gd_place->ID, 'average m2 price', find_average_price($depotrum_data, 0, 1000, 'm2'));
						update_post_meta($gd_place->ID, 'average m3 price', find_average_price($depotrum_data, 0, 1000, 'm3'));
					   
						update_post_meta($gd_place->ID, 'mini size average price', find_average_price($depotrum_data, 0, 2, ''));
						update_post_meta($gd_place->ID, 'mini size average m2 price', find_average_price($depotrum_data, 0, 2, 'm2'));
						update_post_meta($gd_place->ID, 'mini size average m3 price', find_average_price($depotrum_data, 0, 2, 'm3'));
						update_post_meta($gd_place->ID, 'small size average price', find_average_price($depotrum_data, 2, 7, ''));
						update_post_meta($gd_place->ID, 'small size average m2 price', find_average_price($depotrum_data, 2, 7, 'm2'));
						update_post_meta($gd_place->ID, 'small size average m3 price', find_average_price($depotrum_data, 2, 7, 'm3'));

						update_post_meta($gd_place->ID, 'medium size average price', find_average_price($depotrum_data, 7, 18, ''));
						update_post_meta($gd_place->ID, 'medium size average m2 price', find_average_price($depotrum_data, 7, 18, 'm2'));
						update_post_meta($gd_place->ID, 'medium size average m3 price', find_average_price($depotrum_data, 7, 18, 'm3'));

						update_post_meta($gd_place->ID, 'large size average price', find_average_price($depotrum_data, 18, 25, ''));
						update_post_meta($gd_place->ID, 'large size average m2 price', find_average_price($depotrum_data, 18, 25, 'm2'));
						update_post_meta($gd_place->ID, 'large size average m3 price', find_average_price($depotrum_data, 18, 25, 'm3'));

						update_post_meta($gd_place->ID, 'very large size average price', find_average_price($depotrum_data, 25, 1000, ''));
						update_post_meta($gd_place->ID, 'very large size average m2 price', find_average_price($depotrum_data, 25, 1000, 'm2'));
						update_post_meta($gd_place->ID, 'very large size average m3 price', find_average_price($depotrum_data, 25, 1000, 'm3'));
				   }
	}
}

// Unschedule the event on plugin deactivation
register_deactivation_hook(__FILE__, 'my_deactivation_function');

function deactivate_daily_event() {
    // Unschedule the event
    wp_clear_scheduled_hook('update_data_event');
}
?>
