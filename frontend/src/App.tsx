import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import Dashboard from './pages/Dashboard'
import Orders from './pages/Orders'
import Users from './pages/Users'
import Plans from './pages/Plans'
import Services from './pages/Services'
import Groups from './pages/Groups'
import Panels from './pages/Panels'
import Subscriptions from './pages/Subscriptions'
import Discounts from './pages/Discounts'
import Tickets from './pages/Tickets'
import Reports from './pages/Reports'
import SettingsBot from './pages/SettingsBot'
import SettingsGeneral from './pages/SettingsGeneral'
import Broadcast from './pages/Broadcast'
import Backups from './pages/Backups'
import Warehouse from './pages/Warehouse'
import Volumes from './pages/Volumes'
import Login from './pages/Login'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/" element={<Layout />}>
        <Route index element={<Dashboard />} />
        <Route path="orders" element={<Orders />} />
        <Route path="users" element={<Users />} />
        <Route path="plans" element={<Plans />} />
        <Route path="services" element={<Services />} />
        <Route path="groups" element={<Groups />} />
        <Route path="panels" element={<Panels />} />
        <Route path="subscriptions" element={<Subscriptions />} />
        <Route path="discounts" element={<Discounts />} />
        <Route path="tickets" element={<Tickets />} />
        <Route path="reports" element={<Reports />} />
        <Route path="broadcast" element={<Broadcast />} />
        <Route path="settings/bot" element={<SettingsBot />} />
        <Route path="settings/general" element={<SettingsGeneral />} />
        <Route path="backups" element={<Backups />} />
        <Route path="warehouse" element={<Warehouse />} />
        <Route path="volumes" element={<Volumes />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
