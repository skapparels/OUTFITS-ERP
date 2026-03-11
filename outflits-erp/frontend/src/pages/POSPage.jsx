import React, { useState } from 'react';

const mock = [
  { sku: 'SKU-TSHIRT-BLK-M', name: 'T-Shirt Black M', price: 799 },
  { sku: 'SKU-DENIM-BLU-32', name: 'Denim Blue 32', price: 1899 }
];

export default function POSPage() {
  const [cart, setCart] = useState([]);
  const addItem = (item) => setCart((c) => [...c, item]);
  const total = cart.reduce((s, i) => s + i.price, 0);

  return (
    <div className="grid md:grid-cols-2 gap-6">
      <div>
        <h2 className="text-xl font-semibold">POS Billing</h2>
        {mock.map((item) => (
          <button key={item.sku} className="block border rounded p-2 my-2 w-full text-left" onClick={() => addItem(item)}>
            {item.name} - ₹{item.price}
          </button>
        ))}
      </div>
      <div>
        <h2 className="text-xl font-semibold">Cart</h2>
        <ul>{cart.map((item, i) => <li key={`${item.sku}-${i}`}>{item.name}</li>)}</ul>
        <p className="mt-4 font-bold">Total: ₹{total}</p>
        <p className="text-sm text-slate-600">Payment methods: cash, UPI, card, mixed, reward points.</p>
      </div>
    </div>
  );
}
