import React from 'react';
import { Link } from 'react-router-dom';

export default function AppLayout({ children }) {
  return (
    <div className="min-h-screen bg-slate-100">
      <header className="bg-slate-900 text-white p-4 flex gap-4">
        <Link to="/">Admin</Link>
        <Link to="/store">Store</Link>
        <Link to="/pos">POS</Link>
      </header>
      <main className="p-6">{children}</main>
    </div>
  );
}
