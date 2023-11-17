<?php
/**
 * Retrieves all gd_place IDs from the current archive result.
 *
 * @return array An array of gd_place IDs.
 */
function get_all_gd_place_ids_from_archive_result() {
    $all_post_ids = array();

    // Loop through each post in the current archive result
    if (have_posts()) :
        while (have_posts()) : the_post();
            // Check if the post type is 'gd_place'
            if (get_post_type() === 'gd_place') {
                // Add gd_place ID to the array
                $all_post_ids[] = get_the_ID();
            }
        endwhile;
    endif;

    return $all_post_ids;
}

/**
 * Retrieves depotrum data for a list of gd_places.
 *
 * @param array $gd_place_ids_list An array of gd_place IDs.
 * @return array An array containing combined depotrum data for the specified list of gd_places.
 */
function get_statistics_data_for_list_of_gd_places($gd_place_ids_list) {
    $statistics_data = [];
    
    // Loop through each gd_place ID in the provided list
    foreach ($gd_place_ids_list as $gd_place_id) {
        // Get depotrum data for a single gd_place
        $statistics_data_for_single_gd_place = get_statistics_data_for_single_gd_place($gd_place_id);
        
        // Check if depotrum data is available for the gd_place
        if ($statistics_data_for_single_gd_place) {
            // Combine depotrum data for all gd_places in the list
            $statistics_data = array_merge($statistics_data, $statistics_data_for_single_gd_place);
        }
    }
    
    return $statistics_data;
}

function get_statistics_data_for_single_gd_place($gd_place_id) {
	$fields_array = array(
    'num of units available',
    'num of m2 available',
    'num of m3 available',
    'average price',
    'average m2 price',
    'average m3 price',
    'mini size average price',
    'mini size average m2 price',
    'mini size average m3 price',
    'small size average price',
    'small size average m2 price',
    'small size average m3 price',
    'medium size average price',
    'medium size average m2 price',
    'medium size average m3 price',
    'large size average price',
    'large size average m2 price',
    'large size average m3 price',
    'very large size average price',
    'very large size average m2 price',
    'very large size average m3 price'
	);
	
	$return_array = [];
	
	foreach ($fields_array as $field) {
		$value = get_post_meta($gd_place_id, $field, true);
		$return_array[$field] = $value;
		}

    return $return_array;
}

/**
 * Counts the number of depotrum units available in the provided depotrum data.
 *
 * @param array $depotrum_data An array containing depotrum data.
 * @return int The total number of depotrum units available.
 */
function find_num_of_units_available_($depotrum_data) {
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
function find_num_of_m2_or_m3_available_($depotrum_data, $m2_or_m3) {
    $alladded = 0;

    // Loop through each item in $depotrum_data
    foreach ($depotrum_data as $depotrum_data_item) {
        // Determine which value to use based on $m2_or_m3
        // If $m2_or_m3 is 'm2', add 'm2' to the total; otherwise, add 'm3'
        if ($m2_or_m3 === 'm2') {
            $alladded += $depotrum_data_item['m2'];
        } elseif ($m2_or_m3 === 'm3') {
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
function find_average_price_($depotrum_data, $min, $max, $m2_or_m3) {
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
        if ($value_to_use >= $min && $value_to_use <= $max) {
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


function custom_location_text_shortcode() {
    $gd_place_ids_list = get_all_gd_place_ids_from_archive_result();
	
	$depotrum_data = get_statistics_data_for_list_of_gd_places($gd_place_ids_list);
	/*
	echo "average price: " . find_average_price($depotrum_data, 0, 1000,'') . "<br>";
	echo "average m2 price: " . find_average_price($depotrum_data, 0, 1000, 'm2') . "<br>";
	echo "average m3 price: " . find_average_price($depotrum_data, 0, 1000, 'm3') . "<br>";
	echo "mini size average price: " . find_average_price($depotrum_data, 0, 2, '') . "<br>";
	echo "mini size average m2 price: " . find_average_price($depotrum_data, 0, 2, 'm2') . "<br>";
	echo "mini size average m3 price: " . find_average_price($depotrum_data, 0, 2, 'm3') . "<br>";
	echo "small size average price: " . find_average_price($depotrum_data, 2, 7, '') . "<br>";
	echo "small size average m2 price: " . find_average_price($depotrum_data, 2, 7, 'm2') . "<br>";
	echo "small size average m3 price: " . find_average_price($depotrum_data, 2, 7, 'm3') . "<br>";
	echo "medium size average price: " . find_average_price($depotrum_data, 7, 18, '') . "<br>";
	echo "medium size average m2 price: " . find_average_price($depotrum_data, 7, 18, 'm2') . "<br>";
	echo "medium size average m3 price: " . find_average_price($depotrum_data, 7, 18, 'm3') . "<br>";
	echo "large size average price: " . find_average_price($depotrum_data, 18, 25, '') . "<br>";
	echo "large size average m2 price: " . find_average_price($depotrum_data, 18, 25, 'm2') . "<br>";
	echo "large size average m3 price: " . find_average_price($depotrum_data, 18, 25, 'm3') . "<br>";
	echo "very large size average price: " . find_average_price($depotrum_data, 25, 1000, '') . "<br>";
	echo "very large size average m2 price: " . find_average_price($depotrum_data, 25, 1000, 'm2') . "<br>";
	echo "very large size average m3 price: " . find_average_price($depotrum_data, 25, 1000, 'm3') . "<br>";
	
	echo  "num of units available: " . find_num_of_units_available($depotrum_data,0,1000)."<br>";
	echo  "num of m2 available: " . find_num_of_m2_or_m3_available($depotrum_data,"m2")."<br>";
	echo  "num of m3 available: " . find_num_of_m2_or_m3_available($depotrum_data,"m3")."<br>";*/
}

// Register the shortcode.
add_shortcode("custom_location_text", "custom_location_text_shortcode");

?>
