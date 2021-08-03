<?php
/*
Plugin Name: api football
Plugin URI: 
Description: plugin to collect football data
Author: Mouad   
Author URI:
Version: 1

*/
//create a post for the leagues ids
/*add_action('init' , 'register_leagueId');
function register_leagueId(){
	register_post_type('league id' , [
		'label' => 'League',// the last change i made is here 
		'public' => true ,
		'capability_type' => 'post'
	]);
}*/
// schedule the function every hour
function corn_add_minute( $schedules ) {
    $schedules['everyminute'] = array(
            'interval'  => 30,
            'display'   => __( 'every minute' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'corn_add_minute' );
// schedule the action if it's not scheluled
function cronstarter_activation() {
	if ( ! wp_next_scheduled( 'cornjob' ) ) {
		wp_schedule_event( time(), 'everyminute', 'cornjob' );
	}
}
add_action('wp', 'cronstarter_activation');
//hook 
add_action( 'cornjob', 'get_fixture_currentFixtures');

//process to store all informations about fixtures
add_action('wp_ajax_nopriv_get_fixture_currentFixtures', 'get_fixture_currentFixtures');
add_action('wp_ajax_get_fixture_currentFixtures', 'get_fixture_currentFixtures');
function get_fixture_currentFixtures() {		
	// call api using php curl type
	$all_post_ids = get_posts(array(
		'fields'          => 'ids',
		'posts_per_page'  => -1,
		'post_type' => 'leagues'
    ));    
    echo nl2br($all_post_ids);
	foreach( $all_post_ids as $name => $value):
		$id = get_field('field_6109111740600',$value);
		echo nl2br("id for ".$value." = ".$id."\r\n");
	endforeach;
    foreach( $all_post_ids as $name => $value):
        $id = get_field('field_6109111740600',$value);
        echo $id;
        /*date("Y-m-d")*/
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => "https://api-football-v1.p.rapidapi.com/v2/fixtures/league/". $id ."/2021-08-13?timezone=Europe%2FLondon",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => [
				"x-rapidapi-host: api-football-v1.p.rapidapi.com",
				"x-rapidapi-key: 2069692945mshe4a47c4d010dbbcp175af6jsnc0c64429b138"
			],
		]);
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {			
			$response = json_decode($response);		
        }	
        echo $response->api->fixtures[$i]->homeTeam->team_name;
		//get all data from api and create a post for each row and store it
		$maxresult = $response->api->results;					
		if ($maxresult != 0){
			for($i=0 ; $i<$maxresult ;$i++){
				$fixture_slug = sanitize_title($response->api->fixtures[$i]->homeTeam->team_name .' vs '.$response->api->fixtures[$i]->awayTeam->team_name .' in '.$response->api->fixtures[$i]->league->name);
				echo $fixture_slug;
				$inserted_fixture = wp_insert_post([
					'post_name' => $fixture_slug,
					'post_title' => $fixture_slug,
					'post_type' => 'current_fixtures',
					'post_status' => 'publish'
				]);		 
				echo $fixture_slug;
				$fixture = array(
					'fixture_id' => $response->api->fixtures[$i]->fixture_id,
					'event_date' => $response->api->fixtures[$i]->event_date,
					'event_time' => $response->api->fixtures[$i]->event_timestamp,
					'first_half_start' => $response->api->fixtures[$i]->firstHalfStart,
					'second_half_start' => $response->api->fixtures[$i]->secondHalfStart,
					'round' => $response->api->fixtures[$i]->round,
					'status' => $response->api->fixtures[$i]->status,
					'short_status' => $response->api->fixtures[$i]->statusShort,
					'elapsed' => $response->api->fixtures[$i]->elapsed,
					'venue' => $response->api->fixtures[$i]->venue,
					'referee' => $response->api->fixtures[$i]->referee,
					'halftime' => $response->api->fixtures[$i]->score->halftime,
					'fulltime' => $response->api->fixtures[$i]->score->fulltime,
					'extratime' => $response->api->fixtures[$i]->score->extratime,
					'penalty' => $response->api->fixtures[$i]->score->penalty,									
				);
			    $league = array(
					'league_id' => $response->api->fixtures[$i]->league_id,
					'name' => $response->api->fixtures[$i]->league->name,
					'country' => $response->api->fixtures[$i]->league->country,
					'logo' => $response->api->fixtures[$i]->league->logo,
					'flag' => $response->api->fixtures[$i]->league->flag,
				);	
				$homeTeam = array(
					'id' => $response->api->fixtures[$i]->homeTeam->team_id,
					'name' => $response->api->fixtures[$i]->homeTeam->team_name,
					'logo' => $response->api->fixtures[$i]->homeTeam->logo,
					'goals' => $response->api->fixtures[$i]->goalsHomeTeam,
				);
				$awayTeam = array(
					'id' => $response->api->fixtures[$i]->awayTeam->team_id,
					'name' => $response->api->fixtures[$i]->awayTeam->team_name,
					'logo' => $response->api->fixtures[$i]->awayTeam->logo,
					'goals' => $response->api->fixtures[$i]->goalsAwayTeam,
				);	
				update_field('field_61090f31a2899', $fixture , $inserted_fixture);
				update_field('field_60935305ce341', $league , $inserted_fixture);
				update_field('field_60946f4a3c58b', $homeTeam , $inserted_fixture);
				update_field('field_60946f923c590', $awayTeam , $inserted_fixture);
			}
		}
	endforeach;
}


/**
 * Add monthly interval to the schedules (since WP doesnt provide it from the start)
 */
add_filter('cron_schedules','cron_add_every_three_days');
function cron_add_every_three_days($schedules) {
$schedules['every_three_days'] = array(
  'interval' => 259200,
  'display' => __( 'Once per 3 days' )
);
return $schedules;
}
/**
 * Add the scheduling if it doesnt already exist
 */
add_action('wp','setup_schedule');
function setup_schedule() {
  if (!wp_next_scheduled('monthly_pruning') ) {
    wp_schedule_event( time(), 'every_three_days', 'monthly_pruning');
  }
}
/**
 * Add the function that takes care of removing all rows with post_type=post that are older than 30 days
 */
add_action( 'wp', 'delete_expired_coupons_daily' );
function delete_expired_coupons_daily() {
    if ( ! wp_next_scheduled( 'delete_expired_coupons' ) ) {
        wp_schedule_event( time(), 'every_three_days', 'delete_expired_coupons');
    }
}
add_action( 'delete_expired_coupons', 'delete_expired_coupons_callback' );
function delete_expired_coupons_callback() {
    $args = array(
        'post_type' => 'coupon',// change the name 
        'posts_per_page' => -1
    );

    $coupons = new WP_Query($args);
    if ($coupons->have_posts()):
        while($coupons->have_posts()): $coupons->the_post();    

            $expiration_date = get_post_meta( get_the_ID(), 'expiry_date', true );
            $expiration_date_time = strtotime($expiration_date);

            if ($expiration_date_time < time()) {
                wp_delete_post(get_the_ID(), true);
                //Use wp_delete_post(get_the_ID(),true) to delete the post from the trash too.                  
            }

        endwhile;
    endif;
}