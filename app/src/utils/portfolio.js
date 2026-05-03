/**
 * Layouts del portfolio del tema kotlis.
 * Los códigos `stN` se mantienen porque es lo que el tema lee del meta `rnr_wr_port_dt_opt`.
 * Aquí mapeamos a nombres legibles para la UI.
 */

export const PORTFOLIO_LAYOUTS = [
  { code: 'st1', label: 'Column Grid (con sidebar)', short: 'Column Grid' },
  { code: 'st2', label: 'Carousel',                  short: 'Carousel' },
  { code: 'st3', label: 'Column Full Width',         short: 'Full Width' },
  { code: 'st4', label: 'Full Screen Slider',        short: 'Slider' },
];

const byCode = Object.fromEntries(PORTFOLIO_LAYOUTS.map(l => [l.code, l]));

export function layoutLabel(code, fallback = '?') {
  return byCode[code]?.label || fallback;
}

export function layoutShort(code, fallback = '?') {
  return byCode[code]?.short || fallback;
}
