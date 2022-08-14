<?php 
  class Weather {
    // DB stuff
    private $conn;
    private $forecast_table = 'forecast_histories';
    private $alert_table = 'alerts';
    private $api_base_url = 'http://api.weatherapi.com/v1';
    private $api_key = '7d3f50cf71a94912bb681950220707';

    // Weather Properties
    private $search_params;
    private $search_type;
    private $days;

    private $request_made_at;
    private $date;
    private $weekday;
    private $weather_condition;

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }

    // Get current weather
    public function get_current_weather($_search_params) {
      $this->search_params = $_search_params;
      $url = $this->api_base_url . '/current.json?key=' . $this->api_key . '&q=' . $this->search_params;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
    
    // Get forecast weather
    public function get_forecast_weather($_search_type, $_search_params, $_days, $_insert = true) {
      $this->search_params = $_search_params;
      $this->days = $_days;
      $url = $this->api_base_url . '/forecast.json?key=' . $this->api_key . '&q=' . $this->search_params . '&days=' . $this->days;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);

      $this->search_type = $_search_type;
      if($_insert) {
        $this->insert_weather_data(json_decode($result)->forecast->forecastday);
      }
      return $result;
    }

    // Insert weather data into DB
    private function insert_weather_data($_data) {
      $this->request_made_at = date('Y-m-d H:i:s');
      foreach($_data as $data) {
        $this->date = $data->date;
        $this->weekday = $this->get_weekday($this->date);
        $this->weather_condition = $data->day->condition->text;

        $query = 'INSERT INTO ' .
          $this->forecast_table .
          ' (search_type, search_params, request_made_at, date, weekday, weather_condition)
          VALUES (:search_type, :search_params, :request_made_at, :date, :weekday, :weather_condition)';

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':search_type', $this->search_type, PDO::PARAM_STR);
        $stmt->bindParam(':search_params', $this->search_params, PDO::PARAM_STR);
        $stmt->bindParam(':request_made_at', $this->request_made_at, PDO::PARAM_STR);
        $stmt->bindParam(':date', $this->date, PDO::PARAM_STR);
        $stmt->bindParam(':weekday', $this->weekday, PDO::PARAM_STR);
        $stmt->bindParam(':weather_condition', $this->weather_condition, PDO::PARAM_STR);
        $stmt->execute();
      }
    }
    
    private function get_weekday($_date) {
      $date = new DateTime($_date);
      return $date->format('l');
    }

    public function daily_check(){
      $query = 'SELECT search_type, search_params, request_made_at, COUNT(*) as count FROM ' . $this->forecast_table . ' WHERE date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY search_params, request_made_at ';
      $stmt = $this->conn->prepare($query);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $this->compare_result($result);
    }

    private function get_last_14_days_forecast_history_by_search_params($_search_params, $_request_made_at){
      $query = 'SELECT * FROM ' . $this->forecast_table . ' WHERE date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND search_params = :search_params AND request_made_at = :request_made_at';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':search_params', $_search_params, PDO::PARAM_STR);
      $stmt->bindParam(':request_made_at', $_request_made_at, PDO::PARAM_STR);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $result;
    }

    private function compare_result($_result) {
      foreach($_result as $key => $value) {
        $result_old = $this->get_last_14_days_forecast_history_by_search_params($value['search_params'], $value['request_made_at']);
        $result_new = json_decode($this->get_forecast_weather($value['search_type'], $value['search_params'], $value['count'], false))->forecast->forecastday;
        foreach($result_new as $key => $value) {
          if($value->date == $result_old[$key]['date']) {
            if($value->day->condition->text != $result_old[$key]['weather_condition']) {
              // Update weather condition
              $this->update_weather_condition($result_old[$key]['id'], $value->day->condition->text);
              // Insert alert
              $this->insert_alert($result_old[$key]['id'], $result_old[$key]['weekday'], $result_old[$key]['weather_condition'], $value->day->condition->text);
            } 
          }
        }
      }
    }

    private function update_weather_condition($_id, $_weather_condition) {
      $query = 'UPDATE ' . $this->forecast_table . ' SET weather_condition = :weather_condition WHERE id = :id';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':weather_condition', $_weather_condition, PDO::PARAM_STR);
      $stmt->bindParam(':id', $_id, PDO::PARAM_INT);
      $stmt->execute();
    }

    private function insert_alert($_id, $_weekday, $_weather_condition_old, $_weather_condition_new) {
      $this->summary = 'stating the change from ' . $_weather_condition_old . ' to ' . $_weather_condition_new . '.';
      $query = 'INSERT INTO ' . $this->alert_table . ' (forecast_history_id, weekday, summary) VALUES (:forecast_history_id, :weekday, :summary)';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':forecast_history_id', $_id, PDO::PARAM_STR);
      $stmt->bindParam(':weekday', $_weekday, PDO::PARAM_STR);
      $stmt->bindParam(':summary', $this->summary, PDO::PARAM_STR);
      $stmt->execute();
    }

  }