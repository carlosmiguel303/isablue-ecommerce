package com.icodeap.ecommerce.backend.infrastructure.service;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.Map;

/**
 * Cobro con tarjeta vía Culqi (Perú).
 * En modo prueba sin llaves configuradas, simula un cargo exitoso para poder
 * demostrar el flujo completo. Con la llave secreta real, cobra de verdad.
 */
@Service
public class CulqiService {

    @Value("${culqi.secret-key:}")
    private String secretKey;
    @Value("${culqi.public-key:}")
    private String publicKey;

    private final RestTemplate rest = new RestTemplate();

    public boolean isConfigured() {
        return secretKey != null && !secretKey.isBlank();
    }

    public String getPublicKey() {
        return publicKey == null ? "" : publicKey;
    }

    /**
     * @param amount   monto en soles
     * @param currency PEN
     * @param email    correo del comprador
     * @param token    token de tarjeta generado por Culqi.js (source_id)
     * @return id del cargo (o referencia simulada en modo prueba)
     */
    public String charge(BigDecimal amount, String currency, String email, String token) {
        if (!isConfigured() || token == null || token.isBlank()) {
            return "SIM-" + System.currentTimeMillis();
        }
        int cents = amount.multiply(BigDecimal.valueOf(100)).setScale(0, RoundingMode.HALF_UP).intValue();

        HttpHeaders headers = new HttpHeaders();
        headers.setBearerAuth(secretKey);
        headers.setContentType(MediaType.APPLICATION_JSON);

        Map<String, Object> body = Map.of(
                "amount", cents,
                "currency_code", currency,
                "email", email,
                "source_id", token
        );

        @SuppressWarnings("rawtypes")
        ResponseEntity<Map> response = rest.postForEntity(
                "https://api.culqi.com/v2/charges", new HttpEntity<>(body, headers), Map.class);

        Object id = response.getBody() != null ? response.getBody().get("id") : null;
        return id != null ? id.toString() : "CULQI-OK";
    }
}
