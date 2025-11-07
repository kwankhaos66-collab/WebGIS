<?php
// ข้อมูลเชื่อมต่อฐานข้อมูล
$host = "host=localhost";
$port = "port=5432";
$dbname = "dbname=agi66";
$credentials = "user=postgres password=postgres";

// เชื่อมต่อฐานข้อมูล PostgreSQL
$db = pg_connect("$host $port $dbname $credentials");

// ตรวจสอบการเชื่อมต่อ
if (!$db) {
    header('Content-Type: application/json');
    echo json_encode(['type' => 'FeatureCollection', 'features' => []]);
    exit;
}

// กำหนดค่าเริ่มต้น
$sql_where = "";
$limit = "";

// รับค่าจาก URL Query
$type = isset($_GET['type']) ? $_GET['type'] : 'initial';
$value = isset($_GET['value']) ? $_GET['value'] : '';

// สร้างเงื่อนไขการค้นหา
if ($type === 'initial') {
    // โหลดข้อมูลทั้งหมดเมื่อโหลดหน้าครั้งแรก
    //$limit = "LIMIT 30"; 
} else if ($type === 's_name' && !empty($value)) {
    $escaped_value = pg_escape_string($value);
    // ค้นหาชื่อหรือรหัสนักศึกษา
    $sql_where = "WHERE s_name ILIKE '%{$escaped_value}%' OR s_id::text ILIKE '%{$escaped_value}%'";
} else if ($type === 'curriculum' && !empty($value)) {
    $escaped_value = pg_escape_string($value);
    // การค้นหาตามหลักสูตร (ใช้ % ครอบเพื่อให้ค้นหาบางส่วน)
    $sql_where = "WHERE curriculum ILIKE '%{$escaped_value}%'"; 
} else if ($type === 'faculty' && !empty($value)) { 
    $escaped_value = pg_escape_string($value);
    // ค้นหาตรงตามจังหวัด
    $sql_where = "WHERE province ILIKE '{$escaped_value}'";
}

// สร้าง SQL Query เต็ม
$sql = "
    SELECT 
        *, 
        ST_AsGeoJSON(ST_SetSRID(ST_MakePoint(long::double precision, lat::double precision), 4326)) AS geojson 
    FROM 
        students65 
    {$sql_where} 
    {$limit};
";

$query = pg_query($db, $sql);

// สร้าง GeoJSON
$geojson = array(
    'type' => 'FeatureCollection',
    'features' => array()
);

if ($query) {
    while ($row = pg_fetch_assoc($query)) {
        $geometry_data = json_decode($row['geojson'], true);

        if ($geometry_data && $geometry_data['type'] === 'Point') {
            $feature = array(
                'type' => 'Feature',
                'geometry' => $geometry_data,
                'properties' => array(
                    's_id' => $row['s_id'],
                    's_name' => $row['s_name'],
                    'curriculum' => $row['curriculum'],
                    'department' => $row['department'],
                    'faculty' => $row['faculty'],
                    'graduated_school' => $row['graduated_school'],
                    'subdistrict' => $row['subdistrict'],
                    'district' => $row['district'],
                    'province' => $row['province']
                )
            );
            array_push($geojson['features'], $feature);
        }
    }
}

// ปิดการเชื่อมต่อ
pg_close($db);

// ส่ง Header เป็น JSON และแสดงผล
header('Content-Type: application/json');
echo json_encode($geojson, JSON_UNESCAPED_UNICODE);
?>