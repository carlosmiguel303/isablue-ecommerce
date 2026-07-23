package com.icodeap.ecommerce.backend.infrastructure.jwt;

import io.jsonwebtoken.Claims;
import org.junit.jupiter.api.Test;

import java.util.List;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Cubre la generación y verificación de tokens y, sobre todo, la regla de seguridad
 * que exige un secreto de al menos 64 caracteres (mitiga secretos débiles).
 */
class JWTGeneratorTest {

    private static final String STRONG_SECRET =
            "clave-de-prueba-suficientemente-larga-para-hs512-1234567890-abcdefg";

    @Test
    void rejectsSecretShorterThan64Chars() {
        assertThrows(IllegalArgumentException.class,
                () -> new JWTGenerator("corta", 1500000L));
    }

    @Test
    void generatesAndParsesTokenWithSubjectAndAuthorities() {
        JWTGenerator generator = new JWTGenerator(STRONG_SECRET, 1500000L);

        String bearer = generator.getToken("cliente@isablue.pe", List.of("ROLE_USER"));
        assertTrue(bearer.startsWith(Constants.TOKEN_BEARER_PREFIX));

        String raw = bearer.substring(Constants.TOKEN_BEARER_PREFIX.length());
        Claims claims = generator.parseToken(raw);

        assertEquals("cliente@isablue.pe", claims.getSubject());
        assertTrue(claims.get("authorities").toString().contains("ROLE_USER"));
    }

    @Test
    void parseRejectsTamperedToken() {
        JWTGenerator generator = new JWTGenerator(STRONG_SECRET, 1500000L);
        String bearer = generator.getToken("cliente@isablue.pe", List.of("ROLE_USER"));
        String raw = bearer.substring(Constants.TOKEN_BEARER_PREFIX.length());

        String tampered = raw.substring(0, raw.length() - 2) + (raw.endsWith("aa") ? "bb" : "aa");
        assertThrows(RuntimeException.class, () -> generator.parseToken(tampered));
    }
}
