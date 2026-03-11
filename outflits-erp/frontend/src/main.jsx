import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import AppLayout from './layouts/AppLayout';
import AdminDashboard from './pages/AdminDashboard';
import StoreDashboard from './pages/StoreDashboard';
import POSPage from './pages/POSPage';

const App = () => (
  <BrowserRouter>
    <AppLayout>
      <Routes>
        <Route path="/" element={<AdminDashboard />} />
        <Route path="/store" element={<StoreDashboard />} />
        <Route path="/pos" element={<POSPage />} />
      </Routes>
    </AppLayout>
  </BrowserRouter>
);

createRoot(document.getElementById('root')).render(<App />);
