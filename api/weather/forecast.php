<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');

  include_once '../../config/Database.php';
  include_once '../../models/Weather.php';

  // ini_set('display_errors', '1');
  // ini_set('display_startup_errors', '1');
  // error_reporting(E_ALL);

  // Instantiate DB & connect
  $database = new Database();
  $db = $database->connect();

  // Instantiate blog post object
  $weather = new Weather($db);

  if(isset($_GET['days'])) {

    // Check if days is within range 10-14
    if($_GET['days'] >= 10 && $_GET['days'] <= 14) {

      if(isset($_GET['search_type'])) {

        switch($_GET['search_type']) {
          case 'postcode':
            if(!isset($_GET['search_params'])) {
              $result = json_encode(
                array('message' => 'UK postcode is not set')
              );
              break;
            }
            // To save api call credit, examine search param whether it is a valid UK postcode district before making api call
            // ctwheels's second answer: https://stackoverflow.com/questions/164979/regex-for-matching-uk-postcodes
            if(preg_match('/^([A-Z]{1,2}\d[A-Z\d]?|GIR ?0A{2})$/', $_GET['search_params'])) {
              $result = $weather->get_forecast_weather($_GET['search_type'], $_GET['search_params'], $_GET['days']);
            } else {
              $result = json_encode(
                array('message' => 'Invalid postcode district')
              );
            }
            break;
          case 'latlong':
            if(!isset($_GET['search_params'])) {
              $result = json_encode(
                array('message' => 'Latlong is not set')
              );
              break;
            }
            // To save api call credit, examine search param whether it is a valid lat/long before making api call
            $latlong = explode(',', $_GET['search_params']);
            $lat = $latlong[0];
            $long = $latlong[1];
            if(preg_match('/^-?([0-8]?[0-9]|90)(\.[0-9]{1,10})$/',$lat)){
              if(preg_match('/^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})$/',$long)){
                $result = $weather->get_forecast_weather($_GET['search_type'], $latlong, $_GET['days']);
              } else {
                $result = json_encode(
                  array('message' => 'Invalid lat/long')
                );
              }
            }
            break;
          case 'ip':
            $ip = $_SERVER['REMOTE_ADDR'];
            // $ip = '51.194.161.148'; // Hardcode ip for testing
            $result = $weather->get_forecast_weather($_GET['search_type'], $ip, $_GET['days']);
            break;
          default:
            $result = json_encode(
              array('message' => 'Invalid search type')
            );
        }

        echo $result;
    
      } else {
        $result = json_encode(
          array('message' => 'Search type is not set')
        );
        echo $result;
      }

    } else {
      $result = json_encode(
        array('message' => 'Days must be between 10 and 14')
      );
      echo $result;
    }

  } else {
    $result = json_encode(
      array('message' => 'Days is not set')
    );
    echo $result;
  }


