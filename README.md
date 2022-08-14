# Weather API
This is a technical task for You Agency

## Quick Start
Import the weather_api.sql file, change the params in the config/Database.php file to your own

## End Points
1. Get current weather
Endpoint: /api/weather/current.php
Method: GET

| Params | Input |
| - | - |
| search_type | postcode / latlong / ip |
| search_params | UK postcode district e.g. SW1 / latlong e.g. 48.8567,2.3508 / ip (Detection of client ip) |

2. Get weather forecast
Endpoint: /api/weather/forecast.php
Method: GET

| Params | Input |
| - | - |
| search_type | postcode / latlong / ip |
| search_params | UK postcode district e.g. SW1 / latlong e.g. 48.8567,2.3508 / ip (Detection of client ip) |
| days | 10 - 14 |

3. Cron job, check forecast changes and insert changes to alerts table
Endpoint: /api/weather/cron.php
The way to make the cron job running depends on the OS.

### Author
Ho Yin Wong

### Version
1.0.0

### License
This project is licensed under the MIT License