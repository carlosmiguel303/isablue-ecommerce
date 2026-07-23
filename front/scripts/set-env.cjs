const fs = require('fs');
const path = require('path');

// Genera environment.prod.ts a partir de variables de entorno en el build.
// No contiene secretos: solo URL pública de la API e identidad visible de la tienda.
const apiUrl = process.env.API_URL || 'https://TU-BACKEND-RAILWAY.up.railway.app/api/v1';
const storeName = process.env.STORE_NAME || 'Tienda';
const storeWhatsapp = process.env.STORE_WHATSAPP || '';

const esc = (v) => String(v).replace(/'/g, "\\'");
const target = path.join(__dirname, '..', 'src', 'environments', 'environment.prod.ts');
const contents =
  `export const environment = {\n` +
  `  production: true,\n` +
  `  apiUrl: '${esc(apiUrl)}',\n` +
  `  store: {\n` +
  `    name: '${esc(storeName)}',\n` +
  `    whatsapp: '${esc(storeWhatsapp)}'\n` +
  `  }\n` +
  `};\n`;

fs.writeFileSync(target, contents, 'utf8');
console.log(`environment.prod.ts generado para ${apiUrl} (tienda: ${storeName})`);
