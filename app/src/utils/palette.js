/**
 * Extracción de paleta dominante de una imagen mediante muestreo + cuantización simple.
 * Sin dependencias. Funciona con un File u objectURL.
 *
 * Algoritmo:
 *  1. Cargar imagen en un canvas pequeño (96x96).
 *  2. Recorrer todos los píxeles y cuantizar a una rejilla de 32 niveles por canal.
 *  3. Histograma de buckets (R5,G5,B5) → coger los N más frecuentes.
 *  4. Filtrar negros casi puros, blancos casi puros y grises pálidos.
 *  5. Devolver hex strings en mayúsculas (#RRGGBB), top N (default 5).
 */

export async function extractPalette(file, count = 5) {
  if (!file || !file.type?.startsWith('image/')) return [];
  const url = URL.createObjectURL(file);
  try {
    const img = await loadImage(url);
    return paletteFromImage(img, count);
  } finally {
    URL.revokeObjectURL(url);
  }
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload  = () => resolve(img);
    img.onerror = (e) => reject(new Error('No se pudo decodificar la imagen'));
    img.src = src;
  });
}

function paletteFromImage(img, count) {
  const SIZE = 96;
  const canvas = document.createElement('canvas');
  canvas.width = SIZE;
  canvas.height = SIZE;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  ctx.drawImage(img, 0, 0, SIZE, SIZE);
  const data = ctx.getImageData(0, 0, SIZE, SIZE).data;

  // Cuantización a 5 bits por canal → 32^3 buckets posibles, sólo guardamos los usados
  const buckets = new Map();
  for (let i = 0; i < data.length; i += 4) {
    const r = data[i], g = data[i + 1], b = data[i + 2], a = data[i + 3];
    if (a < 128) continue;

    const lum = (0.2126 * r + 0.7152 * g + 0.0722 * b);

    // Filtrar extremos: muy oscuros, muy claros y grises desaturados
    if (lum < 20 || lum > 235) continue;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    const sat = max === 0 ? 0 : (max - min) / max;
    if (sat < 0.08) continue;

    const key = (r >> 3) * 1024 + (g >> 3) * 32 + (b >> 3);
    const e = buckets.get(key);
    if (e) {
      e.count++;
      e.r += r; e.g += g; e.b += b;
    } else {
      buckets.set(key, { count: 1, r, g, b });
    }
  }

  // Ordenar por count desc y promediar el color dentro de cada bucket
  const sorted = [...buckets.values()]
    .sort((a, b) => b.count - a.count)
    .slice(0, count * 4); // tomamos más para luego dedupe

  // Quitar colores muy parecidos (distancia euclidea < 30)
  const final = [];
  for (const b of sorted) {
    const r = Math.round(b.r / b.count);
    const g = Math.round(b.g / b.count);
    const bl = Math.round(b.b / b.count);
    const tooClose = final.some(c =>
      Math.sqrt((c.r - r) ** 2 + (c.g - g) ** 2 + (c.b - bl) ** 2) < 30
    );
    if (!tooClose) {
      final.push({ r, g, b: bl });
      if (final.length >= count) break;
    }
  }

  return final.map(c => '#' + toHex(c.r) + toHex(c.g) + toHex(c.b));
}

function toHex(n) {
  const h = Math.max(0, Math.min(255, n)).toString(16).toUpperCase();
  return h.length === 1 ? '0' + h : h;
}
