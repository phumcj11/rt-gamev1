import { getGameSession, isGameComplete } from '../lib/session.js';
import { pickRandomReward, generateRewardCode } from '../lib/rewards.js';
import { savePlayer } from '../lib/db.js';

const BRANCHES = ['siam_paragon', 'central_world', 'iconsiam', 'phuket', 'chiang_mai'];

export default async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json; charset=utf-8');

  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  try {
    const session = await getGameSession(req, res);

    if (!isGameComplete(session)) {
      return res.status(400).json({ success: false, message: 'Please complete the game first' });
    }

    const body = typeof req.body === 'string' ? JSON.parse(req.body) : req.body;
    const name = String(body?.name || '').trim();
    const phone = String(body?.phone || '').trim();
    const branch = String(body?.branch || '').trim();
    const nationality = String(body?.nationality || '').trim();
    const lang = body?.lang === 'th' ? 'th' : 'en';

    if (!name || !phone || !branch || !nationality) {
      return res.status(400).json({ success: false, message: 'Please fill in all fields' });
    }

    if (!/^[0-9+\-\s()]{8,20}$/.test(phone)) {
      return res.status(400).json({ success: false, message: 'Invalid phone number' });
    }

    if (!BRANCHES.includes(branch)) {
      return res.status(400).json({ success: false, message: 'Invalid branch' });
    }

    const reward = session.game.pending_reward || pickRandomReward();
    const code = generateRewardCode();

    await savePlayer({
      name,
      phone,
      branch,
      nationality,
      items_collected: 3,
      reward_code: code,
      reward_type: reward.reward_key,
      reward_label_en: reward.label_en,
      reward_label_th: reward.label_th,
    });

    session.game.saved_player = {
      code,
      reward_en: reward.label_en,
      reward_th: reward.label_th,
      name,
    };
    session.game.items = [];
    session.game.pending_reward = null;
    await session.save();

    const redirect = `/reward.html?lang=${lang}&saved=1&code=${encodeURIComponent(code)}&reward=${encodeURIComponent(lang === 'th' ? reward.label_th : reward.label_en)}&name=${encodeURIComponent(name)}`;

    return res.status(200).json({
      success: true,
      code,
      reward: lang === 'th' ? reward.label_th : reward.label_en,
      redirect,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ success: false, message: 'Unable to save' });
  }
}
