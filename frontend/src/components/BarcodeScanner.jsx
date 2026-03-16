import { useEffect, useRef, useState } from 'react';

/**
 * Scans a book barcode (ISBN) using the native BarcodeDetector API (Chrome/Edge),
 * then looks up metadata via Google Books API.
 *
 * Props:
 *   onBookFound({ title, author, image }) — called when book data is retrieved
 *   onClose() — called to dismiss the scanner
 */
const BarcodeScanner = ({ onBookFound, onClose }) => {
  const videoRef = useRef(null);
  const streamRef = useRef(null);
  const animFrameRef = useRef(null);
  const [status, setStatus] = useState('Initialisation de la caméra…');
  const [error, setError] = useState(null);
  const [scanning, setScanning] = useState(true);

  const stopCamera = () => {
    if (animFrameRef.current) cancelAnimationFrame(animFrameRef.current);
    if (streamRef.current) streamRef.current.getTracks().forEach((t) => t.stop());
  };

  const handleClose = () => {
    stopCamera();
    onClose();
  };

  const fetchBookByISBN = async (isbn) => {
    setStatus(`ISBN détecté : ${isbn} — recherche en cours…`);
    setScanning(false);
    stopCamera();
    try {
      const res = await fetch(
        `https://www.googleapis.com/books/v1/volumes?q=isbn:${isbn}`
      );
      const json = await res.json();
      if (!json.items || json.items.length === 0) {
        setError('Livre non trouvé sur Google Books. Essayez un autre code ou saisissez manuellement.');
        return;
      }
      const info = json.items[0].volumeInfo;
      const title = info.title || '';
      const author = info.authors ? info.authors.join(', ') : '';
      const image = info.imageLinks?.thumbnail || info.imageLinks?.smallThumbnail || '';
      onBookFound({ title, author, image });
    } catch {
      setError('Erreur réseau lors de la recherche Google Books.');
    }
  };

  useEffect(() => {
    if (!('BarcodeDetector' in window)) {
      setError(
        'Votre navigateur ne supporte pas le scanner de code-barres (BarcodeDetector). ' +
        'Utilisez Chrome ou Edge sur desktop/Android.'
      );
      return;
    }

    let detector;
    try {
      detector = new window.BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128'] });
    } catch {
      setError('Impossible d\'initialiser le lecteur de code-barres.');
      return;
    }

    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: 'environment' } })
      .then((stream) => {
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.play();
        }
        setStatus('Pointez la caméra vers le code-barres du livre…');

        const scan = async () => {
          if (!videoRef.current || videoRef.current.readyState < 2) {
            animFrameRef.current = requestAnimationFrame(scan);
            return;
          }
          try {
            const barcodes = await detector.detect(videoRef.current);
            if (barcodes.length > 0) {
              const raw = barcodes[0].rawValue;
              // Keep only EAN-13/ISBN-13 (starts with 978/979) or EAN-10
              if (/^(978|979)\d{10}$/.test(raw) || /^\d{9}[\dX]$/.test(raw)) {
                await fetchBookByISBN(raw);
                return;
              }
            }
          } catch {
            // detection frame error — keep looping
          }
          animFrameRef.current = requestAnimationFrame(scan);
        };

        animFrameRef.current = requestAnimationFrame(scan);
      })
      .catch(() => {
        setError('Accès à la caméra refusé. Autorisez la caméra dans les paramètres du navigateur.');
      });

    return () => stopCamera();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-[60]">
      <div className="bg-white rounded-lg p-4 w-full max-w-sm flex flex-col items-center gap-4">
        <div className="flex justify-between items-center w-full">
          <h3 className="text-lg font-bold">Scanner un livre</h3>
          <button
            onClick={handleClose}
            className="text-gray-500 hover:text-gray-800 text-2xl leading-none"
            aria-label="Fermer"
          >
            &times;
          </button>
        </div>

        {!error && (
          <div className="relative w-full rounded overflow-hidden bg-black" style={{ aspectRatio: '4/3' }}>
            <video
              ref={videoRef}
              className="w-full h-full object-cover"
              muted
              playsInline
            />
            {/* Aiming crosshair */}
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className="border-2 border-blue-400 w-3/4 h-1/4 rounded opacity-80" />
            </div>
          </div>
        )}

        {error ? (
          <div className="text-red-600 text-sm text-center">{error}</div>
        ) : (
          <p className="text-sm text-gray-600 text-center">{status}</p>
        )}

        <button
          onClick={handleClose}
          className="w-full py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm"
        >
          Annuler / Saisie manuelle
        </button>
      </div>
    </div>
  );
};

export default BarcodeScanner;
