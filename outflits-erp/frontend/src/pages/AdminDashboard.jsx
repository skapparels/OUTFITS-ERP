import React from 'react';
import KPIWidget from '../components/KPIWidget';

export default function AdminDashboard() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">OUTFLITS ERP - Admin Dashboard</h1>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <KPIWidget label="Total Sales" value="₹12,40,000" />
        <KPIWidget label="Clearance Styles" value="248" />
        <KPIWidget label="Low Stock Alerts" value="37" />
      </div>
    </div>
  );
}
