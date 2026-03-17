import { useEffect, useRef, useState } from 'react';
import { BrowserMultiFormatReader, DecodeHintType, BarcodeFormat } from '@zxing/library';

/**
 * Scans a book barcode (ISBN) using @zxing/library (cross-browser: Chrome, Firefox, Safari, Edge),
 * then looks up metadata via Google Books API.
 *
 * Props:
 *   onBookFound({ title, author, image }) — called when book data is retrieved
 *   onClose() — called to dismiss the scanner
 */
const BarcodeScanner = ({ onBookFound, onClose }) => {
  const videoRef = useRef(null);
  const readerRef = useRef(null);
  const hasScannedRef = useRef(false); // prevent double-trigger
  const [status, setStatus] = useState('Initialisation de la caméra…');
  const [error, setError] = useState(null);

  const stopCamera = () => {
    if (readerRef.current) {
      readerRef.current.reset();
    }
  };

  const handleClose = () => {
    stopCamera();
    onClose();
  };

  /** Extract a clean ISBN-13 or ISBN-10 from a raw scanned string (barcode or QR URL). */
  const extractISBN = (raw) => {
    // Raw ISBN-13 (978/979 prefix, 13 digits)
    const isbn13Match = raw.match(/(?:^|[^\d])(97[89]\d{10})(?:[^\d]|$)/);
    if (isbn13Match) return isbn13Match[1];
    // Raw ISBN-10 (9 digits + digit or X)
    const isbn10Match = raw.match(/(?:^|[^\d])(\d{9}[\dX])(?:[^\d]|$)/);
    if (isbn10Match) return isbn10Match[1];
    return null;
  };

  const fetchBookByISBN = async (isbn) => {
    setStatus(`ISBN détecté : ${isbn} — recherche en cours…`);
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
    const hints = new Map();
    hints.set(DecodeHintType.POSSIBLE_FORMATS, [
      BarcodeFormat.EAN_13,
      BarcodeFormat.EAN_8,
      BarcodeFormat.CODE_128,
      BarcodeFormat.QR_CODE,
    ]);
    hints.set(DecodeHintType.TRY_HARDER, true);

    const codeReader = new BrowserMultiFormatReader(hints);
    readerRef.current = codeReader;

    codeReader
      .decodeFromConstraints(
        { video: { facingMode: 'environment' } },
        videoRef.current,
        (result, err) => {
          if (result && !hasScannedRef.current) {
            const raw = result.getText();
            const isbn = extractISBN(raw);
            if (isbn) {
              hasScannedRef.current = true;
              fetchBookByISBN(isbn);
            } else {
              // Code detected but not an ISBN — show feedback without stopping
              setStatus(`Code détecté (non ISBN) : ${raw.slice(0, 40)}…`);
            }
          }
          if (err && err.name !== 'NotFoundException') {
            // Ignore NotFoundException (no barcode in frame) — it fires every frame
            console.warn('ZXing error:', err);
          }
        }
      )
      .then(() => {
        setStatus('Pointez la caméra vers le code-barres du livre…');
      })
      .catch((err) => {
        if (err.name === 'NotAllowedError') {
          setError('Accès à la caméra refusé. Autorisez la caméra dans les paramètres du navigateur.');
        } else {
          setError(`Impossible d'accéder à la caméra : ${err.message}`);
        }
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
