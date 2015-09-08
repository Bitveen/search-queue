CREATE TABLE rest_data (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  breath INT UNSIGNED,
  digestion INT UNSIGNED,
  sight INT UNSIGNED,
  nervous_system INT UNSIGNED,
  urogenital_system INT UNSIGNED,
  blood_circulation INT UNSIGNED,
  skin_diseases INT UNSIGNED
);

INSERT INTO rest_data (breath, digestion, sight, nervous_system, urogenital_system, blood_circulation, skin_diseases) VALUES (
    2, 3, 4, 1, 4, NULL, 2
);