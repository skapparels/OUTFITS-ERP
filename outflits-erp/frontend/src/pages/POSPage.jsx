import React, { useMemo, useState } from 'react';
import { api } from '../services/api';
import { clearOfflineReferences, enqueueOfflineSale, getOfflineSalesQueue } from '../services/offlineSalesQueue';
import { useOfflineStatus } from '../hooks/useOfflineStatus';

const mock = [
  { sku: 'SKU-TSHIRT-BLK-M', variantId: 1, name: 'T-Shirt Black M', price: 799 },
  { sku: 'SKU-DENIM-BLU-32', variantId: 2, name: 'Denim Blue 32', price: 1899 }
];

export default function POSPage() {
  const [cart, setCart] = useState([]);
  const [syncMessage, setSyncMessage] = useState('');
  const [storeId] = useState(1);
  const { isOnline } = useOfflineStatus();

  const addItem = (item) => setCart((c) => [...c, item]);
  const total = useMemo(() => cart.reduce((s, i) => s + i.price, 0), [cart]);
  const pendingCount = getOfflineSalesQueue().length;

  const buildPayload = () => ({
    store_id: storeId,
    payment_method: 'cash',
    items: cart.map((item) => ({
      product_variant_id: item.variantId,
      quantity: 1,
      unit_price: item.price,
    }))
  });

  const checkout = async () => {
    if (!cart.length) return;

    const payload = buildPayload();
    if (!isOnline) {
      const queued = enqueueOfflineSale(payload);
      setCart([]);
      setSyncMessage(`Offline saved. Ref ${queued.offline_reference}. Queue: ${getOfflineSalesQueue().length}`);
      return;
    }

    try {
      await api.post('/sales', payload);
      setCart([]);
      setSyncMessage('Sale billed online successfully.');
    } catch {
      const queued = enqueueOfflineSale(payload);
      setCart([]);
      setSyncMessage(`Network/API failed. Saved offline as ${queued.offline_reference}.`);
    }
  };

  const syncOfflineSales = async () => {
    const queue = getOfflineSalesQueue();
    if (!queue.length) {
      setSyncMessage('No offline bills pending sync.');
      return;
    }

    try {
      const { data } = await api.post('/sales/offline-sync', { sales: queue });
      const syncedRefs = (data.synced || []).map((row) => row.offline_reference);
      clearOfflineReferences(syncedRefs);
      setSyncMessage(`Synced ${data.synced_count} bills. Duplicates: ${data.duplicate_count}. Remaining: ${getOfflineSalesQueue().length}`);
    } catch {
      setSyncMessage('Sync failed. Please retry when connection is stable.');
    }
  };

  return (
    <div className="grid md:grid-cols-2 gap-6">
      <div>
        <h2 className="text-xl font-semibold">POS Billing (Offline-ready)</h2>
        <p className={`text-sm ${isOnline ? 'text-green-700' : 'text-red-600'}`}>
          Status: {isOnline ? 'Online' : 'Offline'} | Pending offline bills: {pendingCount}
        </p>

        {mock.map((item) => (
          <button key={item.sku} className="block border rounded p-2 my-2 w-full text-left" onClick={() => addItem(item)}>
            {item.name} - ₹{item.price}
          </button>
        ))}

        <div className="flex gap-2 mt-4">
          <button className="bg-slate-900 text-white px-4 py-2 rounded" onClick={checkout}>Checkout</button>
          <button className="bg-blue-700 text-white px-4 py-2 rounded" onClick={syncOfflineSales}>Sync Offline Bills</button>
        </div>
      </div>

      <div>
        <h2 className="text-xl font-semibold">Cart</h2>
        <ul>{cart.map((item, i) => <li key={`${item.sku}-${i}`}>{item.name}</li>)}</ul>
        <p className="mt-4 font-bold">Total: ₹{total}</p>
        <p className="text-sm text-slate-600">Payment methods: cash, UPI, card, mixed, reward points.</p>
        {syncMessage ? <p className="mt-3 text-sm text-indigo-700">{syncMessage}</p> : null}
      </div>
    </div>
  );
}
