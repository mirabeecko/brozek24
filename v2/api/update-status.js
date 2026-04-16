/**
 * GET/POST /api/update-status?key=SECRET&status=online&slots=2
 *
 * Přepíná online/offline status v Vercel KV.
 * Používejte z iOS Shortcuts nebo záložky v prohlížeči.
 *
 * Parametry:
 *   key     — tajný klíč (nastavte jako env var STATUS_SECRET_KEY ve Vercel)
 *   status  — "online" nebo "offline"
 *   slots   — (volitelné) číslo 0–9, počet volných slotů
 */
import { kv } from '@vercel/kv';

export default async function handler(req, res) {
  res.setHeader('Content-Type', 'application/json');

  const params   = req.method === 'POST' ? req.body : req.query;
  const key      = params?.key    ?? '';
  const status   = params?.status ?? '';
  const slots    = params?.slots;
  const SECRET   = process.env.STATUS_SECRET_KEY ?? 'CHANGE_THIS_KEY_BEFORE_DEPLOY_2026';

  // Auth
  if (!key || key !== SECRET) {
    return res.status(403).json({ error: 'Unauthorized.' });
  }

  // Validace
  if (!['online', 'offline'].includes(status)) {
    return res.status(400).json({ error: 'Neplatný status. Použijte: online | offline.' });
  }

  // Přečíst stávající data (zachovat slots_total atd.)
  let existing = {};
  try {
    existing = (await kv.get('mb_status')) ?? {};
  } catch (e) {}

  const data = {
    ...existing,
    status,
    last_update: new Date().toISOString(),
  };

  if (slots !== undefined && slots !== null && !isNaN(Number(slots))) {
    data.slots_remaining = Math.max(0, Math.min(9, Number(slots)));
  }

  await kv.set('mb_status', data);

  res.status(200).json({ ok: true, data });
}
