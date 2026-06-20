import { getGameSession, addCollectedItem, getCollectedCount, isGameComplete } from '../lib/session.js';
import { pickRandomReward } from '../lib/rewards.js';

export default async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json; charset=utf-8');

  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  try {
    const body = typeof req.body === 'string' ? JSON.parse(req.body) : req.body;
    const itemId = String(body?.item_id || '').trim();
    if (!itemId) {
      return res.status(400).json({ success: false, message: 'Invalid item' });
    }

    const session = await getGameSession(req, res);
    const added = addCollectedItem(session, itemId);
    const count = getCollectedCount(session);
    const complete = isGameComplete(session);

    if (complete && !session.game.pending_reward) {
      session.game.pending_reward = pickRandomReward();
    }

    await session.save();

    return res.status(200).json({
      success: true,
      added,
      count,
      required: 3,
      complete,
      reward: complete ? session.game.pending_reward : null,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ success: false, message: 'Server error' });
  }
}
