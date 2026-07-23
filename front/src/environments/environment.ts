export const environment = {
  production: false,
  apiUrl: 'http://localhost:8085/api/v1',
  // Identidad de la tienda (para desarrollo local). En producción se genera desde
  // variables de entorno en build (scripts/set-env.cjs).
  store: {
    name: 'Tienda Demo',
    whatsapp: '51999999999'
  }
};
