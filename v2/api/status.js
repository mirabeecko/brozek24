/**
 * GET /api/status
 * Vrátí aktuální status ze Vercel KV.
 * Pokud KV není nastaveno, vrátí výchozí hodnoty.
 */
import { kv } from '@vercel/kv';

export default async function handler(req, res) {
  res.setHeader('Cache-Control', 'no-store');
  res.setHeader('Content-Type', 'application/json');

  try {
    const data = await kv.get('mb_status');
    if (data) {
      return res.status(200).json(data);
    }
  } catch (e) {
    // KV není nakonfigurováno — fallback na výchozí
  }

  // Výchozí stav (než poprvé aktualizujete přes /api/update-status)
  res.status(200).json({
    status: 'online',
    last_update: new Date().toISOString(),
    slots_remaining: 2,
    slots_total: 3,
    next_slot_hours: 9
  });
}
