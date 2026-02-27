import { useEffect } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './store'
import Layout from './components/Layout'
import Home from './pages/Home'
import Forums from './pages/Forums'
import Chat from './pages/Chat'
import Library from './pages/Library'
import Maps from './pages/Maps'
import Profile from './pages/Profile'
import ProfileEdit from './pages/ProfileEdit'
import Settings from './pages/Settings'
import Login from './pages/Login'
import Register from './pages/Register'
import ForgotPassword from './pages/ForgotPassword'
import AdminInterface from './pages/AdminInterface'
import Conversation from './pages/Conversation'
import Methodology from './pages/Methodology'
import Chill from './pages/Chill'
import Administrative from './pages/Administrative'
import MethodologyForums from './pages/MethodologyForums'
import DetenteForums from './pages/DetenteForums'
import CafeDesLumieresForums from './pages/CafeDesLumieresForums'
import AdministratifForums from './pages/AdministratifForums'

function App() {
  const { checkAuth, isLoading } = useAuthStore()

  useEffect(() => {
    checkAuth()
  }, [checkAuth])

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-xl">Chargement...</div>
      </div>
    )
  }

  return (
    <Routes>
      <Route path="/" element={<Layout />}>
        <Route index element={<Home />} />
        
        {/* Special Forum Routes */}
        <Route path="methodology-forums" element={<MethodologyForums />} />
        <Route path="methodology-forums/:category" element={<MethodologyForums />} />
        <Route path="methodology-forums/:category/:postId" element={<MethodologyForums />} />
        <Route path="detente-forums" element={<DetenteForums />} />
        <Route path="detente-forums/:category" element={<DetenteForums />} />
        <Route path="detente-forums/:category/:postId" element={<DetenteForums />} />
        <Route path="cafe_des_lumieres-forums" element={<CafeDesLumieresForums />} />
        <Route path="cafe_des_lumieres-forums/:category" element={<CafeDesLumieresForums />} />
        <Route path="cafe_des_lumieres-forums/:category/:postId" element={<CafeDesLumieresForums />} />
        <Route path="administratif-forums" element={<AdministratifForums />} />
        <Route path="administratif-forums/:category" element={<AdministratifForums />} />
        <Route path="administratif-forums/:category/:postId" element={<AdministratifForums />} />
        
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
        <Route path="profile-edit" element={<ProfileEdit />} />
        <Route path="settings" element={<Settings />} />
        
        {/* Messages */}
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
