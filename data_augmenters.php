<?php
///////////////////////////////////////////////////////////////
// Data Augmenters
///////////////////////////////////////////////////////////////
//
// Collection of functions that are used to augment CSV rows
// and generate values calculated from other rows.
//

// Generates a value that augments the 'price' field.
//
// * Displays the 'campaign_message' field above the price.
// * The normal price is striked-out and the campaign price is
//   displayed instead.
// * The 'campaign_message' is a link to 'campaign_link'
// function campaign_message_with_link($row) {
// 	if (isset($row['campaign_link']) && 
// 	    $row['campaign_message'] &&
// 	    $row['campaign_link'] && $row['campaign_message']) {
// 		$message = "<a href='".$row['campaign_link']."' style='font-weight:bold;text-decoration:none;color:red'>".
// 		           $row['campaign_message']."</a><br />\n";
// 	} else {
// 		$message = $row['campaign_message']."<br />\n";
// 	}
// 	return $message."<s>".$row['price']."</s> <span style='color:red'>".$row['campaign_price']."</span>";
// }


// // Tells us if this product has a currently running campaign
// function is_campaign($row) {
// 	return isset($row['starts_at']) && $row['ends_at'] && 
// 	    strtotime($row['starts_at']) <= time() && 
// 	    strtotime($row['ends_at']) >= time();
// }