<?php
    // insert_hiking.php
    
    // ... (ส่วนการเชื่อมต่อฐานข้อมูลเดิม) ...
    $hostname_db = "localhost";
    $database_db = "hiking"; 
    $username_db = "postgres";
    $password_db = "postgres";
    $port_db     = "5432";
    $table_name = "thai_hiking";

    $db = pg_connect("host=$hostname_db port=$port_db dbname=$database_db user=$username_db password=$password_db");
    if (!$db) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(["error" => "Connection failed: " . pg_last_error()]));
    }

    // --- 3. รับและประมวลผลข้อมูลขาเข้า ---
    $lat  = isset($_GET['lat']) ? floatval($_GET['lat']) : null; 
    $lng  = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    $name = isset($_GET['name']) ? pg_escape_string($_GET['name']) : null; 
    
    // **[เพิ่ม]** รับค่าจังหวัดและความสูง
    $province = isset($_GET['province']) ? pg_escape_string($_GET['province']) : '';
    // ใช้ floatval สำหรับตัวเลข (ความสูง) และกำหนดเป็น NULL หากไม่มีค่า
    $elevation_m = isset($_GET['elevation_m']) && is_numeric($_GET['elevation_m']) ? floatval($_GET['elevation_m']) : 'NULL'; 
    
    header('Content-Type: application/json; charset=utf-8');

    if ($lat === null || $lng === null || $name === null || $name === '') {
        echo json_encode(["error" => "Invalid or missing parameters (lat, lng, name)."]);
        pg_close($db);
        exit;
    }

    // --- 4. INSERT จุดใหม่เข้าตาราง ---
    // **[แก้ไข]** เพิ่ม province และ elevation_m ในคำสั่ง INSERT
    $insert_query = "
        INSERT INTO {$table_name} (geom, feature_name, province, elevation_m)
        VALUES (ST_SetSRID(ST_Point($lng, $lat), 4326), '{$name}', '{$province}', {$elevation_m})
    ";
    
    $result = pg_query($db, $insert_query);

    // --- 5. ตอบกลับผลลัพธ์ ---
    if ($result) {
        echo json_encode(["success" => true, "message" => "Data inserted successfully."]);
    } else {
        echo json_encode(["error" => "Database insert failed: " . pg_last_error($db) . " | Query: " . $insert_query]);
    }
    
    pg_close($db);
?>