package com.icodeap.ecommerce.backend.infrastructure.jwt;

import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.SignatureAlgorithm;
import io.jsonwebtoken.security.Keys;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Service;

import java.nio.charset.StandardCharsets;
import java.security.Key;
import java.util.Date;
import java.util.List;

@Service
public class JWTGenerator {
    private final Key signingKey;
    private final long expirationMs;

    public JWTGenerator(
            @Value("${jwt.secret}") String secret,
            @Value("${jwt.expiration-ms:1500000}") long expirationMs
    ) {
        if (secret == null || secret.length() < 64) {
            throw new IllegalArgumentException("JWT_SECRET debe tener al menos 64 caracteres");
        }
        this.signingKey = Keys.hmacShaKeyFor(secret.getBytes(StandardCharsets.UTF_8));
        this.expirationMs = expirationMs;
    }

    public String getToken(String username) {
        List<String> authorities = SecurityContextHolder.getContext()
                .getAuthentication()
                .getAuthorities()
                .stream()
                .map(GrantedAuthority::getAuthority)
                .toList();
        return getToken(username, authorities);
    }

    /** Genera el token con las autoridades indicadas (para login social, sin contexto de seguridad). */
    public String getToken(String username, List<String> authorities) {
        String token = Jwts.builder()
                .setId("isablue-ecommerce")
                .setSubject(username)
                .claim("authorities", authorities)
                .setIssuedAt(new Date())
                .setExpiration(new Date(System.currentTimeMillis() + expirationMs))
                .signWith(signingKey, SignatureAlgorithm.HS512)
                .compact();

        return Constants.TOKEN_BEARER_PREFIX + token;
    }

    public Claims parseToken(String token) {
        return Jwts.parserBuilder()
                .setSigningKey(signingKey)
                .build()
                .parseClaimsJws(token)
                .getBody();
    }
}
