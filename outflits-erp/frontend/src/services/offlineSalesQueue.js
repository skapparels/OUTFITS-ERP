const STORAGE_KEY = 'outflits_offline_sales_queue_v1';

export function getOfflineSalesQueue() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export function enqueueOfflineSale(salePayload) {
  const queue = getOfflineSalesQueue();
  const entry = {
    ...salePayload,
    offline_reference: salePayload.offline_reference || `OFF-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
    sold_at: salePayload.sold_at || new Date().toISOString(),
  };
  queue.push(entry);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
  return entry;
}

export function clearOfflineReferences(references) {
  const removeSet = new Set(references);
  const queue = getOfflineSalesQueue().filter((row) => !removeSet.has(row.offline_reference));
  localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
  return queue;
}
