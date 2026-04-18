#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h> // Library for parsing JSON

// --- Configuration ---
const char* ssid = "CREATIVE CRACK";
const char* password = "2025@@cc";

#ifndef LED_BUILTIN
#define LED_BUILTIN 2
#endif

// Full URL to the PHP read endpoint for this device
const char* serverUrl = "http://192.168.0.110/firefly/iot/api/read.php?device_id=led_1";

const int builtInLedPin = LED_BUILTIN;
const int brightnessPin = 4;
const int pwmFrequency = 5000;
const int pwmResolution = 8;

void setup() {
  Serial.begin(115200);
  
  pinMode(builtInLedPin, OUTPUT);
  pinMode(brightnessPin, OUTPUT);
  ledcAttach(brightnessPin, pwmFrequency, pwmResolution);

  digitalWrite(builtInLedPin, LOW);
  ledcWrite(brightnessPin, 0);

  WiFi.begin(ssid, password);
  Serial.print("Connecting to Wi-Fi");
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\nConnected!");
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    int httpCode = http.GET();

    if (httpCode == 200) { // Check if server responded OK
      String payload = http.getString();
      
      JsonDocument doc; 
      DeserializationError error = deserializeJson(doc, payload);

      if (!error) {
        int ledMain = doc["led_main"] | 0;
        int brightness = doc["brightness"] | 0;
        brightness = constrain(brightness, 0, 255);

        digitalWrite(builtInLedPin, ledMain ? HIGH : LOW);
        ledcWrite(brightnessPin, brightness);
        
        Serial.print("JSON Received: ");
        serializeJson(doc, Serial);
        Serial.println();
      } else {
        Serial.print("JSON Parse Failed: ");
        Serial.println(error.f_str());
      }
      // --- JSON PARSING END ---

    } else {
      Serial.print("HTTP Error: ");
      Serial.println(httpCode);
    }
    http.end();
  } else {
    WiFi.begin(ssid, password);
  }

  delay(500); 
}