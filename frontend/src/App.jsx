import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import Home from './pages/Home'
import Forums from './pages/Forums'
import Chat from './pages/Chat'
import Library from './pages/Library'
import Maps from './pages/Maps'
import Profile from './pages/Profile'
import Login from './pages/Login'
import Register from './pages/Register'
import ForgotPassword from './pages/ForgotPassword'
import AdminInterface from './pages/AdminInterface'
import PrivateMessages from './pages/PrivateMessages'
import Conversation from './pages/Conversation'
import Methodology from './pages/Methodology'
import Chill from './pages/Chill'
import Administrative from './pages/Administrative'

function App() {
  return (
    <Routes>
      <Route path="/" element={<Layout />}>
        <Route index element={<Home />} />
        
        {/* Forum Routes */}
        <Route path="forums/:category" element={<Forums />} />
        <Route path="forums/:category/:postId" element={<Forums />} />
        
        {/* Other Routes */}
        <Route path="chat" element={<Chat />} />
        <Route path="library" element={<Library />} />
        <Route path="maps" element={<Maps />} />
        <Route path="methodology" element={<Methodology />} />
        <Route path="chill" element={<Chill />} />
        <Route path="administrative" element={<Administrative />} />
        <Route path="profile/:username" element={<Profile />} />
        
        {/* Messages */}
        <Route path="messages" element={<PrivateMessages />} />
        <Route path="messages/:conversationId" element={<Conversation />} />
        
        {/* Admin */}
        <Route path="admin" element={<AdminInterface />} />
      </Route>
      
      {/* Auth Routes (no layout) */}
      <Route path="login" element={<Login />} />
      <Route path="register" element={<Register />} />
      <Route path="forgot-password" element={<ForgotPassword />} />
      
      {/* 404 */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default App
