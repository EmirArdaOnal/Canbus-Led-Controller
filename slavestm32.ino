#include "main.h"
#include "flash_utils.h"
#define SLAVE_ID 2
typedef struct { uint16_t start; uint16_t end; } LedRange;
LedRange myRange;
void CAN_RxCallback(CAN_RxHeaderTypeDef *header, uint8_t *data) {
  uint32_t canId = header->StdId;
  if (canId != (0x200 + SLAVE_ID)) return;
  uint8_t cmd = data[0];
  if (cmd == 0x01) {
    myRange.start = (data[1] << 8) | data[2];
    myRange.end   = (data[3] << 8) | data[4];
    flash_save_range(&myRange);
    uint8_t ack[8] = {0x81, 0,0,0,0,0,0,0};
  } else if (cmd == 0x02) {
    uint16_t idx = (data[1] << 8) | data[2];
    uint8_t r = data[3], g = data[4], b = data[5], br = data[6];
    applyLedIndex(idx, r,g,b,br);
  } else if (cmd == 0x03) {
    blink_status_led();
  }
}
void applyLedIndex(uint16_t idx, uint8_t r,uint8_t g,uint8_t b,uint8_t br) {
  if (idx < myRange.start || idx > myRange.end) return;
  uint16_t localIndex = idx - myRange.start;
  uint16_t pin = led_index_to_pin(localIndex);
  if (r+g+b == 0) {
  } else {
  }
}
void flash_save_range(LedRange* range) {
}
uint16_t led_index_to_pin(uint16_t localIndex) {
  return 0;
}
void blink_status_led() {
}