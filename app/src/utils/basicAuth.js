/**
 * Cabecera Authorization: Basic con UTF-8 (btoa solo admite Latin-1 y
 * rompía con usuarios o contraseñas con caracteres como "ñ" o emojis).
 */
export function basicAuth(user, pw) {
  const bytes = new TextEncoder().encode(user + ':' + pw);
  let bin = '';
  bytes.forEach(b => { bin += String.fromCharCode(b); });
  return 'Basic ' + btoa(bin);
}
