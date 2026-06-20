import { sql } from '@vercel/postgres';

export async function savePlayer(player) {
  if (!process.env.POSTGRES_URL) {
    return { saved: false, demo: true };
  }

  await sql`
    INSERT INTO players (name, phone, branch, nationality, items_collected, reward_code, reward_type, reward_label_en, reward_label_th)
    VALUES (${player.name}, ${player.phone}, ${player.branch}, ${player.nationality}, ${player.items_collected}, ${player.reward_code}, ${player.reward_type}, ${player.reward_label_en}, ${player.reward_label_th})
  `;
  return { saved: true, demo: false };
}
