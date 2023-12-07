<?php

# this file is the starting point to your application
# the code below is just to verify your connection works and you can receive events.
# you can replace this file completely
# alternatively hook your methods in here


// Function to establish a database connection
function connectToDatabase()
{
    $servername = "sql-db";
    $username = "codingchallenge";
    $password = "codingchallenge";
    $dbname = "codingchallenge";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        jsonify(["status" => "400", "message" => $conn->connect_error]);
        die();
    }
    return $conn;
}

// Check if the 'events' table exists before creating it
function createEventsTableIfNotExists($conn)
{
    $sql = "SHOW TABLES LIKE 'events'";
    $result = $conn->query($sql);

    if ($result->num_rows == 0) {
        // 'events' table does not exist, create it
        $createTableSQL = "CREATE TABLE events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            timestamp INT NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            activity_id VARCHAR(50) NOT NULL
        )";

        if ($conn->query($createTableSQL) !== TRUE) {
            jsonify(["status" => "400", "message" => $conn->error]);
            die();

        }
    }
}


$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);


switch ($_SERVER['REQUEST_URI']) {
    case '/receive':
        receive($input);
        break;
    case '/longest-activity':
        longTimeActivity($input);
        break;
    case '/activity-by-user':
        activityByUser($input);
        break;
    default:
        $response = ["status" => "404", "message" => "Not Found"];
        jsonify($response);
}

// Create Event 
function receive($data)
{
    if (isset($data['events']) && is_array($data['events'])) {
        $conn = connectToDatabase();
        createEventsTableIfNotExists($conn);

        foreach ($data['events'] as $event) {
            $name = $event['name'];
            $timestamp = $event['timestamp'];
            $user_id = $event['user_id'];
            $activity_id = $event['activity_id'];

            // Prepare SQL statement to insert event data
            $sql = "INSERT INTO events (name, timestamp, user_id, activity_id) VALUES ('$name', '$timestamp', '$user_id', '$activity_id')";

            if ($conn->query($sql) === TRUE) {
                $response = [
                    "status" => "200",
                    "request_method" => $_SERVER['REQUEST_METHOD'],
                    "input" => $data,
                    "message" => "Event data inserted successfully"
                ];

            } else {
                $response = [
                    "status" => "400",
                    "message" => $conn->error
                ];
            }
            jsonify($response);
        }

        $conn->close();
    } else {
        echo json_encode(
            [
                "status" => "400",
                "message" => "Invalid events data"
            ]
        );

    }

}


// Retreive Event by userID  & activityId
function activityByUser($data)
{
    // Endpoint to get time spent by each student on each question in an activity
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($data['activity_id']) && isset($data['user_ids'])) {
        $conn = connectToDatabase();

        $activity_id = $data['activity_id'];
        $user_ids = $data['user_ids'];

        if (!is_array($data['user_ids'])) {
            $response = ["status" => 400, "message" => "User id must be an array"];
            jsonify($response);
        }
        // Prepare SQL query to get time spent by each student on each question in the activity
        $sql = "SELECT user_id, activity_id, name, MAX(timestamp) - MIN(timestamp) AS time_spent 
            FROM events 
            WHERE activity_id = '$activity_id' AND user_id IN ('" . implode("','", $user_ids) . "') 
            GROUP BY user_id, activity_id, name";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $response = array('status' => 200);
            while ($row = $result->fetch_assoc()) {
                $response['data'][] = $row;
            }
        } else {
            $response = ["status" => 200, "message" => "No data found"];
        }
        jsonify($response);
        $conn->close();
    } else {
        $response = ["status" => 400, "error" => "Invalid request parameters"];
        jsonify($response);
    }
}

// To Long time of Activity 
function longTimeActivity($data)
{
    // Endpoint to find the activity taking the longest overall time (on average)
    if (isset($data['longest_activity'])) {
        $conn = connectToDatabase();

        // Prepare SQL query to find the activity taking the longest overall time (on average)
        $sql = "SELECT activity_id, AVG(time_spent) AS average_time
        FROM (
            SELECT activity_id, user_id, MAX(timestamp) - MIN(timestamp) AS time_spent
            FROM events
            GROUP BY activity_id, user_id
        ) AS subquery
        GROUP BY activity_id
        ORDER BY average_time DESC 
        LIMIT 1";

        $result = $conn->query($sql);
        $response = ["status" => 200];
        if ($result && $result->num_rows > 0) {
            $response['data'] = $result->fetch_assoc();
        } else {
            $response = ["message" => "No data found"];
        }

        jsonify($response);

        $conn->close();
    } else {
        $response = ["status" => 400, "error" => "Invalid request parameters"];
        jsonify($response);
    }
}

// Common function to encode array to json 
function jsonify($data)
{
    if (is_array($data)) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}




