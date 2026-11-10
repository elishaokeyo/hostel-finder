-- Run once:  mysql -u root -p < database.sql
CREATE DATABASE IF NOT EXISTS hostel_finder CHARACTER SET utf8mb4;
USE hostel_finder;

CREATE TABLE IF NOT EXISTS places (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  latitude DECIMAL(9,6) NOT NULL,
  longitude DECIMAL(9,6) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS hostels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  address VARCHAR(200) NOT NULL,
  latitude DECIMAL(9,6) NOT NULL,
  longitude DECIMAL(9,6) NOT NULL,
  price_per_month INT NOT NULL,
  room_type ENUM('Single','Double','Bedsitter','Shared') NOT NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 0,
  reviews_count INT NOT NULL DEFAULT 0,
  available_rooms INT NOT NULL DEFAULT 0,
  amenities VARCHAR(255) DEFAULT '',
  phone VARCHAR(30) DEFAULT ''
);

-- SAMPLE coordinates: replace with your real university and hostel locations.
INSERT INTO places (name, latitude, longitude, is_default) VALUES
 ('University (main gate)', -1.2921, 36.8219, 1),
 ('Town centre',            -1.2864, 36.8172, 0),
 ('Bus stage',              -1.3000, 36.8300, 0),
 ('Hospital',               -1.2800, 36.8100, 0);

INSERT INTO hostels (name, address, latitude, longitude, price_per_month, room_type, rating, reviews_count, available_rooms, amenities, phone) VALUES
 ('Acacia Court',  'Behind main gate',  -1.2930, 36.8225, 6500, 'Single',    4.6, 38, 4, 'WiFi,Water,Security,Study room', '0700000001'),
 ('Baobab Lodge',  'Market road',       -1.2880, 36.8190, 4800, 'Shared',    4.1, 21, 9, 'Water,Security',                 '0700000002'),
 ('Cedar Heights', 'Hill estate',       -1.3010, 36.8290, 8200, 'Bedsitter', 4.8, 52, 2, 'WiFi,Water,Security,Parking',    '0700000003'),
 ('Dove Hostel',   'Stage road',        -1.2790, 36.8120, 3900, 'Shared',    3.7, 14, 12,'Water',                          '0700000004'),
 ('Elm Residence', 'Library street',    -1.2915, 36.8240, 5600, 'Double',    4.3, 29, 6, 'WiFi,Water,Security',            '0700000005');
