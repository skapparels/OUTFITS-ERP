import React from 'react';

export default function StoreDashboard() {
  return (
    <section>
      <h1 className="text-2xl font-bold mb-4">Store Dashboard</h1>
      <ul className="list-disc pl-6">
        <li>Attendance and shift status</li>
        <li>Daily store P&L</li>
        <li>Replenishment tasks and dispatch queue</li>
      </ul>
    </section>
  );
}
