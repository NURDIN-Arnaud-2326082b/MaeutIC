/**
 * Constructs the correct path for assets (images, JSON, etc.)
 * In dev: returns '/filename' (BASE_URL is '/')
 * In prod: returns '/react/filename' (BASE_URL is '/react/')
 * 
 * Usage:
 *   <img src={assetPath('logo.png')} />
 *   fetch(assetPath('clusters.json'))
 */
export function assetPath(filename) {
  const base = import.meta.env.BASE_URL
  return `${base}${filename}`
}
