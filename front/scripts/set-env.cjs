const fs = require('fs');
const path = require('path');

const apiUrl = process.env.API_URL || 'https://TU-BACKEND-RAILWAY.up.railway.app/api/v1';
const target = path.join(__dirname, '..', 'src', 'environments', 'environment.prod.ts');
const contents = `export const environment = { production: true, apiUrl: '${apiUrl.replace(/'/g, "\\'")}' };\n`;

fs.writeFileSync(target, contents, 'utf8');
console.log(`environment.prod.ts generado para ${apiUrl}`);
