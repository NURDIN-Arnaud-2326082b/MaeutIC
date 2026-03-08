import { useState, useEffect, useRef } from 'react'

export default function MusicPlayer() {
  const [isPlaying, setIsPlaying] = useState(false)
  const audioRef = useRef(null)

  useEffect(() => {
    const audio = audioRef.current
    const onPlay = () => setIsPlaying(true)
    const onPause = () => setIsPlaying(false)
    if (audio) {
      audio.addEventListener('play', onPlay)
      audio.addEventListener('pause', onPause)
    }
    return () => {
      if (audio) {
        audio.removeEventListener('play', onPlay)
        audio.removeEventListener('pause', onPause)
      }
    }
  }, [])

  return (
    <div className="fixed bottom-5 right-5 bg-white backdrop-blur-md border border-gray-200 shadow-lg rounded-xl p-4 w-72 flex flex-col items-center space-y-2 z-[10000]">
      <audio
        ref={audioRef}
        controls
        loop
        preload="none"
        className="w-full rounded-lg"
      >
        <source src="/audio/clair_de_lune_debussy.mp3?v=1.0" type="audio/mpeg" />
        Votre navigateur ne supporte pas la balise audio.
      </audio>
      <p className="text-sm text-gray-600">🎶 Claude Debussy - Clair de Lune</p>
    </div>
  )
}
