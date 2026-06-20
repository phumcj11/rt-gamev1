export const REWARD_POOL = [
  { reward_key: 'discount_10', label_en: '10% Discount Coupon', label_th: 'คูปองส่วนลด 10%', weight: 30 },
  { reward_key: 'discount_15', label_en: '15% Discount Coupon', label_th: 'คูปองส่วนลด 15%', weight: 20 },
  { reward_key: 'free_gift', label_en: 'Free Souvenir Gift', label_th: 'ของที่ระลึกฟรี', weight: 15 },
  { reward_key: 'lucky_bag', label_en: 'Lucky Shopping Bag', label_th: 'ถุงช้อปปิ้งนำโชค', weight: 20 },
  { reward_key: 'elephant_keychain', label_en: 'Elephant Keychain', label_th: 'พวงกุญแจช้าง', weight: 15 },
];

export const ITEMS_REQUIRED = 3;

export function pickRandomReward() {
  const total = REWARD_POOL.reduce((sum, r) => sum + r.weight, 0);
  let random = Math.floor(Math.random() * total) + 1;
  for (const reward of REWARD_POOL) {
    random -= reward.weight;
    if (random <= 0) return reward;
  }
  return REWARD_POOL[0];
}

export function generateRewardCode() {
  const part = Math.random().toString(36).substring(2, 8).toUpperCase();
  return 'EL' + part;
}
