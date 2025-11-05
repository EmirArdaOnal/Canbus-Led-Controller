#include <SPI.h>
#include <Ethernet.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "mcp_can.h"
#include "mbedtls/md.h"
#include "time.h"

#define CAN_ID_OFFSET 0x200
#define HTTP_TIMEOUT 3000

#define SWITCH_PIN 4
#define ETH_CS 5
#define CAN_CS 15
#define CAN_INT 2

const char* WIFI_SSID = "YOUR_SSID";
const char* WIFI_PASS = "YOUR_PASS";
const char* SERVER_HOST = "yourserver.com";
const int SERVER_PORT = 80;
const char* PATH_CONFIG = "/api/api_slaves.php"; 
const char* PATH_LEDSTATE = "/api/api_get_ledstate.php";
const char* PATH_PING = "/db/last_ping.json";
const char* SECRET = "replace_with_strong_secret";

MCP_CAN CAN(CAN_CS);
byte mac[] = {0xDE,0xAD,0xBE,0xEF,0xFE,0xED};

bool useEthernet = false;

String generate_hmac_token() {
  unsigned long minute = time(NULL) / 60;
  unsigned char result[32];
  mbedtls_md_context_t ctx;
  mbedtls_md_init(&ctx);
  const mbedtls_md_info_t *info = mbedtls_md_info_from_type(MBEDTLS_MD_SHA256);
  mbedtls_md_setup(&ctx, info, 1);
  mbedtls_md_hmac_starts(&ctx, (const unsigned char*)SECRET, strlen(SECRET));
  String minuteStr = String(minute);
  mbedtls_md_hmac_update(&ctx, (const unsigned char*)minuteStr.c_str(), minuteStr.length());
  mbedtls_md_hmac_finish(&ctx, result);
  mbedtls_md_free(&ctx);
  char hex[65]; for (int i=0;i<32;i++) sprintf(hex + i*2, "%02x", result[i]);
  hex[64]=0;
  return String(hex);
}

String httpGet(Client& client, String host, int port, String pathWithQuery) {
  String body = "";
  if (!client.connect(host.c_str(), port)) return "";
  client.print(String("GET ") + pathWithQuery + " HTTP/1.1\r\nHost: " + host + "\r\nConnection: close\r\n\r\n");
  unsigned long t0 = millis(); while(!client.available() && millis()-t0<3000) delay(10);
  if (!client.available()) { client.stop(); return ""; }
  String all="";
  while(client.available()) all += (char)client.read();
  client.stop();
  int p = all.indexOf("\r\n\r\n");
  if (p>=0) body = all.substring(p+4); else body = all;
  return body;
}

void sendConfigToSlave(int slaveId, int start, int end) {
  uint8_t d[8];
  d[0] = 0x01; d[1] = (start >> 8) & 0xFF; d[2] = start & 0xFF;
  d[3] = (end >> 8) & 0xFF; d[4] = end & 0xFF;
  for (int i=5;i<8;i++) d[i]=0;
  uint32_t canId = 0x200 + slaveId;
  CAN.sendMsgBuf(canId, 0, 8, d);
  Serial.printf("Config -> Slave %d (%d-%d)\n", slaveId, start, end);
}

void sendLedIndexToSlave(int slaveId, uint16_t idx, int r,int g,int b,int brightness) {
  uint8_t d[8];
  d[0] = 0x02; d[1] = (idx >> 8) & 0xFF; d[2] = idx & 0xFF;
  d[3] = r; d[4] = g; d[5] = b; d[6] = brightness; d[7]=0;
  uint32_t canId = 0x200 + slaveId;
  CAN.sendMsgBuf(canId,0,8,d);
}

void sendPingToSlave(int slaveId) {
  uint8_t d[8]; d[0]=0x03; for (int i=1;i<8;i++) d[i]=0;
  CAN.sendMsgBuf(0x200 + slaveId,0,8,d);
}

void setup() {
  Serial.begin(115200);
  delay(500);
  pinMode(SWITCH_PIN, INPUT_PULLUP);

  configTime(0,0,"pool.ntp.org","time.google.com");

  if (digitalRead(SWITCH_PIN) == LOW) {
    useEthernet = true;
    SPI.begin(18,19,23);
    Ethernet.init(ETH_CS);
    if (Ethernet.begin(mac) == 0) {
      IPAddress ip(192,168,1,150);
      Ethernet.begin(mac, ip);
    }
    Serial.println("Ethernet up: " + Ethernet.localIP().toString());
  } else {
    useEthernet = false;
    WiFi.begin(WIFI_SSID, WIFI_PASS);
    int tries=0; while(WiFi.status()!=WL_CONNECTED && tries++ < 60){ delay(500); Serial.print("."); } // wait for connection
    Serial.println("\nWiFi connected: " + WiFi.localIP().toString());
  }

  if (CAN.begin(MCP_ANY, CAN_500KBPS, MCP_8MHZ) == CAN_OK) Serial.println("MCP2515 OK");
  else Serial.println("MCP2515 FAIL");
  CAN.setMode(MCP_NORMAL);
}

unsigned long lastFetch = 0;
void loop() {
  if (millis() - lastFetch < 3000) return;
  lastFetch = millis();

  String token = generate_hmac_token();
  String configPath = String("/api/api_slaves.php?token=") + token;
  String cfgJson;
  if (useEthernet) {
    EthernetClient client;
    cfgJson = httpGet(client, SERVER_HOST, SERVER_PORT, configPath);
  } else {
    WiFiClient client;
    cfgJson = httpGet(client, SERVER_HOST, SERVER_PORT, configPath);
  }
  if (cfgJson.length() == 0) { Serial.println("config fetch failed"); return; }
  StaticJsonDocument<4096> cfgDoc;
  auto err = deserializeJson(cfgDoc, cfgJson);
  if (err) { Serial.println("cfg parse error"); return; }

  String ledPath = String("/api/api_get_ledstate.php?token=") + token;
  String ledJson;
  if (useEthernet) {
    EthernetClient client;
    ledJson = httpGet(client, SERVER_HOST, SERVER_PORT, ledPath);
  } else {
    WiFiClient client;
    ledJson = httpGet(client, SERVER_HOST, SERVER_PORT, ledPath);
  }
  if (ledJson.length() == 0) { Serial.println("led fetch failed"); return; }
  StaticJsonDocument<4096> ledDoc;
  err = deserializeJson(ledDoc, ledJson);
  if (err) { Serial.println("led parse err"); return; }

  JsonArray leds = ledDoc["leds"];
  JsonObject color = ledDoc["color"];
  int r = color["r"] | 255;
  if(color.containsKey("r")) r = color["r"];
  int g = color["g"] | 255;
  if(color.containsKey("g")) g = color["g"];
  int b = color["b"] | 255;
  if(color.containsKey("b")) b = color["b"];
  int brightness = ledDoc["brightness"] | 255;
  if(ledDoc.containsKey("brightness")) brightness = ledDoc["brightness"];

  for (JsonObject map : cfgDoc.as<JsonArray>()) {
    int slave_id = map["slave_id"];
    int start = map["led_start"];
    int end = map["led_end"];
    // send config to slave so it saves its range
    sendConfigToSlave(slave_id, start, end);
    delay(10);
    for (int idx = start; idx <= end && idx < (int)leds.size(); idx++) {
      int val = leds[idx] | 0;
      if(leds[idx].is<int>()) val = leds[idx];
      if (val) sendLedIndexToSlave(slave_id, idx, r,g,b,brightness); 
      else sendLedIndexToSlave(slave_id, idx, 0,0,0,0); // off command
      delay(5);
    }
  }

  String pingJson;
  if (useEthernet) {
    EthernetClient client;
    pingJson = httpGet(client, SERVER_HOST, SERVER_PORT, String("/db/last_ping.json?rand=")+String(millis()));
  } else {
    WiFiClient client;
    pingJson = httpGet(client, SERVER_HOST, SERVER_PORT, String("/db/last_ping.json?rand=")+String(millis()));
  }
  if (pingJson.length()>0) {
    StaticJsonDocument<256> pdoc;
    if (!deserializeJson(pdoc, pingJson)) {
      int pid = pdoc["slave"] | 0;
      if(pdoc.containsKey("slave")) pid = pdoc["slave"];
      if (pid > 0) { sendPingToSlave(pid); Serial.printf("Ping -> %d\n", pid); }
    }
  }

  Serial.println("Dispatch cycle done.");
}