import { getIronSession } from 'iron-session';

const password = process.env.SESSION_SECRET || 'ar_elephant_hunt_change_this_secret_key_32chars';

export const sessionOptions = {
  password,
  cookieName: 'elephant_hunt_session',
  cookieOptions: {
    secure: process.env.VERCEL === '1',
    httpOnly: true,
    sameSite: 'lax',
  },
};

export async function getGameSession(req, res) {
  const session = await getIronSession(req, res, sessionOptions);
  if (!session.game) {
    session.game = { items: [], pending_reward: null, saved_player: null };
  }
  return session;
}

export function addCollectedItem(session, itemId) {
  const items = session.game.items || [];
  if (items.length >= 3) return false;
  session.game.items = [...items, `${itemId}_${items.length}`];
  return true;
}

export function getCollectedCount(session) {
  return (session.game.items || []).length;
}

export function isGameComplete(session) {
  return getCollectedCount(session) >= 3;
}
